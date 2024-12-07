import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'item'];

    connect() {
        // Initialiser l'index pour les nouveaux éléments
        this.index = this.itemTargets.length;
    }

    addItem(event) {
        // Récupérer le prototype
        const prototype = this.containerTarget.dataset.prototype;
        
        // Créer le nouvel élément en remplaçant l'index
        const newItem = prototype.replace(/__name__/g, this.index);
        
        // Incrémenter l'index
        this.index++;
        
        // Créer un conteneur temporaire
        const temp = document.createElement('div');
        temp.innerHTML = newItem;
        
        // Ajouter la classe pour le style
        temp.firstElementChild.classList.add('media-item', 'card', 'mb-3');
        temp.firstElementChild.dataset.collectionTypeTarget = 'item';
        
        // Ajouter le nouvel élément au conteneur
        this.containerTarget.appendChild(temp.firstElementChild);
    }

    removeItem(event) {
        // Trouver l'élément parent à supprimer
        const item = event.currentTarget.closest('[data-collection-type-target="item"]');
        
        // Supprimer l'élément
        if (item) {
            item.remove();
        }
    }
}
