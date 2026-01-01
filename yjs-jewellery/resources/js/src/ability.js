import { Ability } from '@casl/ability';
import { abilitiesPlugin } from '@casl/vue';

  // Load abilities from localStorage or set default empty rules
  // const storedPermissions = localStorage.getItem('ability');
  const storedPermissions = localStorage.getItem(`user_ability`);
  const initialAbilities = storedPermissions ? JSON.parse(storedPermissions) : [];

  // Create CASL ability instance
  export const ability = new Ability(initialAbilities);

  // Function to update abilities dynamically
  export const updateAbilities = (newAbilities) => {
    ability.update(newAbilities);
  };

  // Export the CASL plugin
  export { abilitiesPlugin };

