<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use SoftDeletes,HasApiTokens, HasFactory, Notifiable,LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name','last_name', 'email', 'phone', 'password', 'user_type', 'status','mobile_code'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name','last_name', 'email', 'phone', 'password', 'user_type', 'status','mobile_code'
            ])
            ->useLogName('User')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function partner()
    {
        return $this->hasOne(Partner::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id','id');
    }

    public function ability()
    {
        return $this->belongsToMany('App\Models\Permission', 'user_permissions', 'user_id', 'permission_id')
            ->select(DB::raw("'do' as action"), 'slug as subject');
    }

    public function reportingHead()
    {
        return $this->belongsTo(User::class, 'reporting_head');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id');
    }

    public function getAllSubordinateUserIds(&$visited = [])
    {
        if (in_array($this->id, $visited)) {
            return collect();
        }

        $visited[] = $this->id;

        $userIds = collect([$this->id]);
        $directReports = self::where('reporting_head', $this->id)->get();

        foreach ($directReports as $child) {
            $userIds = $userIds->merge($child->getAllSubordinateUserIds($visited));
        }

        return $userIds->unique();
    }

    public function isPartner()
    {
        return $this->user_type === 'partner';
    }

    public function isCustomer()
    {
        return $this->user_type === 'customer';
    }


}
