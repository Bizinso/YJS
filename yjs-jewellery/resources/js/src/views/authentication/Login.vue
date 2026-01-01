<template>
    <div class="login-wrapper">
      <div class="login-card">
        <h2>Welcome Back!</h2>
        <p>Please login to your account</p>
        <form @submit.prevent="login">
          <div class="form-group">
            <input
              type="email"
              v-model="email"
              required
              placeholder="Email"
              class="form-input"
            />
          </div>
          <div class="form-group">
            <input
              type="password"
              v-model="password"
              required
              placeholder="Password"
              class="form-input"
            />
          </div>
          <button type="submit" class="login-btn">Login</button>
          <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
        </form>
      </div>
    </div>
  </template>
  
  <script>
  import axios from '@axios';
  import Swal from 'sweetalert2';
  export default {
    
    data() {
      return {
        email: '',
        password: '',
        errorMessage: ''
      };
    },
    methods: {
      login() {
        if (!this.email || !this.password) {
          this.errorMessage = 'Both fields are required';
          return;
        }
  
        axios
        .post('/login', { email: this.email, password: this.password })
        .then(response => {
          const token = response.data.access_token;  // Assume the token is returned as access_token
          
          // Store the JWT token in localStorage
          localStorage.setItem('access_token', token);

          // Success alert
          Swal.fire({
            title: 'Login Successful!',
            text: 'You will be redirected to the dashboard.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });

          // Redirect to the dashboard after a delay
          setTimeout(() => {
            this.$router.push('/dashboard');
          }, 2000);
        })
        .catch((error) => {
          this.errorMessage = 'Invalid email or password';

          // Show error message
          Swal.fire({
            title: 'Login Failed!',
            text: 'Invalid email or password.',
            icon: 'error',
            timer: 2000,
            showConfirmButton: false
          });
        });
      }
    }
  };
  </script>
<style scoped>
/* Center the login form on the page */
.login-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: linear-gradient(135deg, #81ecec, #6c5ce7);
}

.login-card {
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  padding: 40px 30px;
  text-align: center;
  width: 350px;
  transition: transform 0.3s ease-in-out;
}

.login-card:hover {
  transform: translateY(-10px);
}

h2 {
  font-size: 2rem;
  color: #2d3436;
  margin-bottom: 10px;
}

p {
  color: #636e72;
  margin-bottom: 30px;
}

/* Input styles */
.form-group {
  margin-bottom: 20px;
  position: relative;
}

.form-input {
  width: 100%;
  padding: 12px 20px;
  border: 2px solid #dfe6e9;
  border-radius: 5px;
  background-color: #f5f6fa;
  transition: border-color 0.3s;
  outline: none;
  font-size: 1rem;
}

.form-input:focus {
  border-color: #6c5ce7;
}

.login-btn {
  width: 100%;
  padding: 12px 0;
  background-color: #6c5ce7;
  color: #fff;
  font-size: 1.2rem;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.login-btn:hover {
  background-color: #a29bfe;
}

.error {
  color: red;
  margin-top: 10px;
}

@media (max-width: 768px) {
  .login-card {
    width: 90%;
    padding: 30px 20px;
  }

  h2 {
    font-size: 1.5rem;
  }
}
</style>
