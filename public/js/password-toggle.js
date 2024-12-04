document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les champs de mot de passe
    const passwordFields = document.querySelectorAll('.password-toggle-field');
    
    passwordFields.forEach(field => {
        const input = field.querySelector('input[type="password"]');
        const toggleButton = field.querySelector('.password-toggle-button');
        
        if (toggleButton && input) {
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Basculer le type de l'input
                input.type = input.type === 'password' ? 'text' : 'password';
                
                // Basculer la visibilité des icônes
                const eyeIcon = this.querySelector('.eye-icon');
                const eyeOffIcon = this.querySelector('.eye-off-icon');
                
                if (eyeIcon && eyeOffIcon) {
                    eyeIcon.classList.toggle('hidden');
                    eyeOffIcon.classList.toggle('hidden');
                }
            });
        }
    });
});
