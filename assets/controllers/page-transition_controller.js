import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['content'];
    static values = {
        enterAnimation: String,
        leaveAnimation: String
    };

    connect() {
        // Default animation classes if not provided
        this.enterAnimationValue = this.enterAnimationValue || 'animate-fade-in';
        this.leaveAnimationValue = this.leaveAnimationValue || 'animate-fade-out';

        // Add initial animation class
        this.element.classList.add(this.enterAnimationValue);

        // Listen for Turbo events
        document.addEventListener('turbo:before-visit', this.beforeVisit.bind(this));
        document.addEventListener('turbo:load', this.afterVisit.bind(this));
    }

    disconnect() {
        document.removeEventListener('turbo:before-visit', this.beforeVisit.bind(this));
        document.removeEventListener('turbo:load', this.afterVisit.bind(this));
    }

    beforeVisit(event) {
        // Remove enter animation and add leave animation
        this.element.classList.remove(this.enterAnimationValue);
        this.element.classList.add(this.leaveAnimationValue);
    }

    afterVisit(event) {
        // Remove leave animation and add enter animation
        this.element.classList.remove(this.leaveAnimationValue);
        this.element.classList.add(this.enterAnimationValue);
    }
}
