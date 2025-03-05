// Taille standard pour les tuiles (format A4 avec un ratio de 1:1.414)
const STANDARD_TILE_WIDTH = 300; // Largeur standard en pixels
const STANDARD_TILE_HEIGHT = Math.round(STANDARD_TILE_WIDTH * 1.414); // Hauteur A4 proportionnelle

// Fonction pour charger les miniatures PDF
async function loadPdfThumbnails() {
    const thumbnails = document.querySelectorAll('.pdf-thumbnail');
    
    // Tableau pour stocker les promesses de chargement
    const loadPromises = [];
    
    for (const canvas of thumbnails) {
        const pdfPath = canvas.getAttribute('data-pdf-path');
        if (!pdfPath) continue;
        
        // Ajouter la promesse de chargement au tableau
        loadPromises.push(loadThumbnail(canvas, pdfPath));
    }
    
    // Attendre que toutes les miniatures soient chargées
    await Promise.all(loadPromises);
    
    // Standardiser la taille des tuiles une fois que toutes sont chargées
    standardizeTileSize();
}

// Fonction pour charger une miniature individuelle
async function loadThumbnail(canvas, pdfPath) {
    try {
        // Gérer les chemins absolus et relatifs
        let adjustedPath = pdfPath;
        if (pdfPath.startsWith('/cv-manager/uploads/')) {
            adjustedPath = pdfPath.replace('/cv-manager/uploads/', 'uploads/');
        }
        
        // Charger le document PDF
        const loadingTask = pdfjsLib.getDocument(adjustedPath);
        const pdf = await loadingTask.promise;
        
        // Obtenir la première page
        const page = await pdf.getPage(1);
        
        // Obtenir les dimensions originales de la page
        const originalViewport = page.getViewport({ scale: 1.0 });
        
        // Calculer l'échelle pour obtenir la largeur standard
        const scale = STANDARD_TILE_WIDTH / originalViewport.width;
        
        // Créer un viewport avec l'échelle calculée
        const viewport = page.getViewport({ scale: scale });
        
        // Configurer le canvas avec les dimensions standardisées
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        
        // Rendre la page dans le canvas avec une meilleure qualité
        const context = canvas.getContext('2d');
        const renderContext = {
            canvasContext: context,
            viewport: viewport,
            // Activer l'anticrénelage pour une meilleure qualité
            renderInteractiveForms: false,
            enableWebGL: true
        };
        
        await page.render(renderContext).promise;
        
        // Masquer l'indicateur de chargement
        const loadingDiv = canvas.closest('.tile-pdf-preview').querySelector('.pdf-loading');
        if (loadingDiv) {
            loadingDiv.style.display = 'none';
        }
        
    } catch (error) {
        console.error('Erreur lors du chargement de la miniature PDF:', error);
        canvas.closest('.tile-pdf-preview').innerHTML = '<div class="pdf-error">Erreur de chargement</div>';
    }
}

// Fonction pour standardiser la taille des tuiles
function standardizeTileSize() {
    const tiles = document.querySelectorAll('.candidate-tile');
    
    // Définir une taille standard pour toutes les tuiles
    tiles.forEach(tile => {
        const previewContainer = tile.querySelector('.tile-pdf-preview');
        if (previewContainer) {
            previewContainer.style.width = `${STANDARD_TILE_WIDTH}px`;
            previewContainer.style.height = `${STANDARD_TILE_HEIGHT}px`;
        }
        
        // Définir une largeur fixe pour toutes les tuiles
        tile.style.width = `${STANDARD_TILE_WIDTH + 20}px`; // +20px pour les marges
        tile.style.margin = '0 auto 16px';
    });
}

// Variables globales pour le redimensionnement du PDF
let currentPdf = null;
let currentPdfPath = '';

// Fonction pour afficher le PDF optimisé pour le format A4
async function showPdf(pdfPath, candidateName) {
    const container = document.getElementById('pdfViewer');
    container.innerHTML = ''; // Vider le conteneur
    
    try {
        // Afficher un message de chargement
        container.innerHTML = '<div class="flex justify-center items-center h-full"><div class="text-gray-500">Chargement du PDF...</div></div>';
        
        // Gérer les chemins absolus et relatifs
        let adjustedPath = pdfPath;
        if (pdfPath.startsWith('/cv-manager/uploads/')) {
            adjustedPath = pdfPath.replace('/cv-manager/uploads/', 'uploads/');
        }
        
        console.log('Original path:', pdfPath);
        console.log('Adjusted path:', adjustedPath);
        
        // Charger le document PDF
        const loadingTask = pdfjsLib.getDocument(adjustedPath);
        const pdf = await loadingTask.promise;
        
        // Stocker le PDF et le chemin pour le redimensionnement
        currentPdf = pdf;
        currentPdfPath = pdfPath;
        
        // Rendre le PDF
        await renderPdf();
        
    } catch (error) {
        console.error('Erreur lors du chargement du PDF:', error);
        document.getElementById('pdfViewer').innerHTML = '<div class="p-4 text-red-500">Erreur lors du chargement du PDF</div>';
    }
}

// Fonction pour rendre le PDF avec la taille actuelle du conteneur
async function renderPdf() {
    if (!currentPdf) return;
    
    const container = document.getElementById('pdfViewer');
    
    // Vider le conteneur
    container.innerHTML = '';
    
    // Afficher les pages du PDF
    const numPages = currentPdf.numPages;
    
    // Calculer la meilleure échelle pour afficher une page A4 complète
    // Format A4 : 210mm x 297mm (ratio 1:1.414)
    const containerWidth = container.clientWidth - 20; // Marge de 10px de chaque côté
    const containerHeight = container.clientHeight - 20; // Marge de 10px en haut et en bas
    
    for (let pageNum = 1; pageNum <= numPages; pageNum++) {
        const page = await currentPdf.getPage(pageNum);
        
        // Obtenir les dimensions originales de la page
        const viewport = page.getViewport({ scale: 1 });
        
        // Calculer l'échelle pour s'adapter à la largeur du conteneur
        const scaleWidth = containerWidth / viewport.width;
        
        // Calculer l'échelle pour s'adapter à la hauteur du conteneur
        const scaleHeight = containerHeight / viewport.height;
        
        // Utiliser l'échelle la plus petite pour s'assurer que la page entière est visible
        const scale = Math.min(scaleWidth, scaleHeight, 1.5); // Limiter à 1.5x pour éviter des pages trop grandes
        
        // Créer un viewport à l'échelle
        const scaledViewport = page.getViewport({ scale });
        
        // Créer un canvas pour la page
        const canvas = document.createElement('canvas');
        canvas.className = 'pdf-page';
        canvas.width = scaledViewport.width;
        canvas.height = scaledViewport.height;
        container.appendChild(canvas);
        
        // Rendre la page dans le canvas
        const context = canvas.getContext('2d');
        const renderContext = {
            canvasContext: context,
            viewport: scaledViewport
        };
        
        await page.render(renderContext).promise;
    }
}

// Fonctions pour l'édition des informations des candidats
function toggleEdit(candidateId) {
    // Sélectionner à la fois les lignes du tableau et les tuiles
    const rows = document.querySelectorAll(`.candidate-row[data-id="${candidateId}"]`);
    const tile = document.querySelector(`.candidate-tile[data-id="${candidateId}"]`);
    
    // Traiter les lignes du tableau
    rows.forEach(row => {
        // Masquer les informations et afficher les champs d'édition
        row.querySelectorAll('.candidate-info').forEach(info => {
            info.classList.add('hidden');
        });
        
        row.querySelectorAll('.candidate-edit').forEach(edit => {
            edit.classList.remove('hidden');
        });
    });
    
    // Traiter la tuile si elle existe
    if (tile) {
        // Ouvrir un modal d'édition pour la tuile
        openEditModal(candidateId);
    }
    
    // Masquer le bouton d'édition et afficher les boutons de sauvegarde et d'annulation
    const editToggle = document.querySelector(`.candidate-row[data-id="${candidateId}"] .edit-toggle`);
    const saveButton = document.querySelector(`.candidate-row[data-id="${candidateId}"] .save-button`);
    const cancelButton = document.querySelector(`.candidate-row[data-id="${candidateId}"] .cancel-button`);
    
    if (editToggle) editToggle.classList.add('hidden');
    if (saveButton) saveButton.classList.remove('hidden');
    if (cancelButton) cancelButton.classList.remove('hidden');
}

// Fonction pour ouvrir un modal d'édition pour les tuiles
function openEditModal(candidateId) {
    // Vérifier si un modal existe déjà
    let modal = document.getElementById('editModal');
    
    // Créer le modal s'il n'existe pas
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'editModal';
        modal.className = 'edit-modal';
        document.body.appendChild(modal);
    }
    
    // Récupérer les données du candidat
    const tile = document.querySelector(`.candidate-tile[data-id="${candidateId}"]`);
    if (!tile) return;
    
    const name = tile.querySelector('.tile-name').textContent;
    const position = tile.querySelector('.tile-position').textContent;
    const email = tile.querySelector('.tile-email').textContent;
    const date = tile.querySelector('.tile-date').textContent;
    const comments = tile.querySelector('.tile-comments-hover') ? 
                    tile.querySelector('.tile-comments-hover').textContent : '';
    
    // Remplir le modal avec un formulaire d'édition
    modal.innerHTML = `
        <div class="edit-modal-content">
            <h2>Modifier le candidat</h2>
            <form id="editForm">
                <div class="form-group">
                    <label for="edit-name">Nom</label>
                    <input type="text" id="edit-name" value="${name}" data-field="name">
                </div>
                <div class="form-group">
                    <label for="edit-position">Poste</label>
                    <input type="text" id="edit-position" value="${position}" data-field="position">
                </div>
                <div class="form-group">
                    <label for="edit-email">Email</label>
                    <input type="email" id="edit-email" value="${email}" data-field="email">
                </div>
                <div class="form-group">
                    <label for="edit-date">Date</label>
                    <input type="date" id="edit-date" value="${formatDateForInput(date)}" data-field="application_date">
                </div>
                <div class="form-group">
                    <label for="edit-comments">Commentaires</label>
                    <textarea id="edit-comments" data-field="comments">${comments}</textarea>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="saveEditModal(${candidateId})">Enregistrer</button>
                    <button type="button" onclick="closeEditModal()">Annuler</button>
                </div>
            </form>
        </div>
    `;
    
    // Afficher le modal
    modal.style.display = 'flex';
    
    // Ajouter les styles du modal s'ils n'existent pas déjà
    if (!document.getElementById('modalStyles')) {
        const style = document.createElement('style');
        style.id = 'modalStyles';
        style.textContent = `
            .edit-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                justify-content: center;
                align-items: center;
            }
            .edit-modal-content {
                background-color: white;
                padding: 20px;
                border-radius: 8px;
                width: 80%;
                max-width: 500px;
                max-height: 80vh;
                overflow-y: auto;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
            }
            .form-group input, .form-group textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .form-group textarea {
                min-height: 100px;
            }
            .form-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }
            .form-actions button {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .form-actions button:first-child {
                background-color: #1976d2;
                color: white;
            }
            .form-actions button:last-child {
                background-color: #e0e0e0;
                color: #333;
            }
            [data-theme="dark"] .edit-modal-content {
                background-color: #2d2d2d;
                color: #e0e0e0;
            }
            [data-theme="dark"] .form-group input, 
            [data-theme="dark"] .form-group textarea {
                background-color: #333;
                border-color: #444;
                color: #e0e0e0;
            }
            [data-theme="dark"] .form-actions button:last-child {
                background-color: #444;
                color: #e0e0e0;
            }
        `;
        document.head.appendChild(style);
    }
}

// Fonction pour fermer le modal d'édition
function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Fonction pour enregistrer les modifications depuis le modal
function saveEditModal(candidateId) {
    // Récupérer les valeurs du formulaire
    const data = {
        name: document.getElementById('edit-name').value,
        position: document.getElementById('edit-position').value,
        email: document.getElementById('edit-email').value,
        application_date: document.getElementById('edit-date').value,
        comments: document.getElementById('edit-comments').value
    };
    
    // Construire les paramètres de la requête
    const params = new URLSearchParams({
        update_candidate: candidateId,
        ...data
    });
    
    // Envoyer les données au serveur
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status}`);
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error(`Erreur de parsing JSON: ${e.message}`);
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Fermer le modal
            closeEditModal();
            // Recharger la page
            window.location.reload();
        } else {
            alert(data.error || 'Erreur lors de la mise à jour des informations.');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour des informations.');
    });
}

// Fonction pour formater une date pour un input de type date
function formatDateForInput(dateStr) {
    // Essayer de convertir la date au format YYYY-MM-DD
    try {
        // Supprimer le texte "Date: " si présent
        dateStr = dateStr.replace('Date: ', '');
        
        // Essayer différents formats de date
        const formats = [
            // Format français: DD/MM/YYYY
            {
                regex: /(\d{2})\/(\d{2})\/(\d{4})/,
                format: (match) => `${match[3]}-${match[2]}-${match[1]}`
            },
            // Format ISO: YYYY-MM-DD
            {
                regex: /(\d{4})-(\d{2})-(\d{2})/,
                format: (match) => `${match[1]}-${match[2]}-${match[3]}`
            }
        ];
        
        for (const format of formats) {
            const match = dateStr.match(format.regex);
            if (match) {
                return format.format(match);
            }
        }
        
        // Si aucun format ne correspond, retourner une date vide
        return '';
    } catch (e) {
        console.error('Erreur lors du formatage de la date:', e);
        return '';
    }
}

function cancelEdit(candidateId) {
    const rows = document.querySelectorAll(`.candidate-row[data-id="${candidateId}"]`);
    
    rows.forEach(row => {
        // Afficher les informations et masquer les champs d'édition
        row.querySelectorAll('.candidate-info').forEach(info => {
            info.classList.remove('hidden');
        });
        
        row.querySelectorAll('.candidate-edit').forEach(edit => {
            edit.classList.add('hidden');
        });
    });
    
    // Afficher le bouton d'édition et masquer les boutons de sauvegarde et d'annulation
    const editToggle = document.querySelector(`.candidate-row[data-id="${candidateId}"] .edit-toggle`);
    const saveButton = document.querySelector(`.candidate-row[data-id="${candidateId}"] .save-button`);
    const cancelButton = document.querySelector(`.candidate-row[data-id="${candidateId}"] .cancel-button`);
    
    if (editToggle) editToggle.classList.remove('hidden');
    if (saveButton) saveButton.classList.add('hidden');
    if (cancelButton) cancelButton.classList.add('hidden');
}

function saveCandidate(candidateId) {
    // Récupérer les valeurs des champs d'édition
    const data = {
        name: '',
        position: '',
        email: '',
        application_date: '',
        comments: ''
    };
    const rows = document.querySelectorAll(`.candidate-row[data-id="${candidateId}"]`);
    
    console.log('Saving candidate with ID:', candidateId);
    
    // Récupérer toutes les valeurs des champs d'édition
    rows.forEach(row => {
        // Récupérer les valeurs des champs input
        row.querySelectorAll('.candidate-edit input').forEach(input => {
            const field = input.getAttribute('data-field');
            if (field) {
                data[field] = input.value;
                console.log(`Field ${field}:`, input.value);
            }
        });
        
        // Récupérer les valeurs des champs textarea
        row.querySelectorAll('.candidate-edit textarea').forEach(textarea => {
            const field = textarea.getAttribute('data-field');
            if (field) {
                data[field] = textarea.value;
                console.log(`Field ${field}:`, textarea.value);
            }
        });
    });
    
    // Vérifier que tous les champs requis sont présents
    if (!data.name || !data.position || !data.email) {
        console.error('Missing required fields:', data);
        
        // Récupérer les valeurs directement depuis le tableau
        const candidateRow = document.querySelector(`tr.candidate-row[data-id="${candidateId}"]`);
        if (candidateRow) {
            // Récupérer le nom (première colonne)
            if (!data.name) {
                const nameCell = candidateRow.querySelector('td:nth-child(1)');
                if (nameCell) {
                    const nameInfo = nameCell.querySelector('.candidate-info');
                    if (nameInfo) {
                        data.name = nameInfo.textContent.trim();
                        console.log('Retrieved name from display:', data.name);
                    }
                }
            }
            
            // Récupérer le poste (deuxième colonne)
            if (!data.position) {
                const positionCell = candidateRow.querySelector('td:nth-child(2)');
                if (positionCell) {
                    const positionInfo = positionCell.querySelector('.candidate-info');
                    if (positionInfo) {
                        data.position = positionInfo.textContent.trim();
                        console.log('Retrieved position from display:', data.position);
                    }
                }
            }
        }
    }
    
    console.log('Data to send:', data);
    
    // Construire les paramètres de la requête
    const params = new URLSearchParams({
        update_candidate: candidateId,
        ...data
    });
    
    console.log('Request URL:', 'index.php');
    console.log('Request method:', 'POST');
    console.log('Request body:', params.toString());
    
    // Envoyer les données au serveur via une requête AJAX
    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: params
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', [...response.headers.entries()]);
        
        if (!response.ok) {
            throw new Error(`Erreur réseau: ${response.status} ${response.statusText}`);
        }
        
        return response.text().then(text => {
            console.log('Raw response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                throw new Error(`Erreur de parsing JSON: ${e.message}. Raw response: ${text}`);
            }
        });
    })
    .then(data => {
        console.log('Parsed response data:', data);
        
        if (data.success) {
            // Recharger la page pour afficher les informations mises à jour
            // C'est plus simple et plus fiable que d'essayer de mettre à jour l'interface utilisateur manuellement
            window.location.reload();
            
            // Revenir à l'affichage normal
            cancelEdit(candidateId);
            
            // Afficher un message de succès
            alert('Informations mises à jour avec succès !');
        } else {
            // Afficher le message d'erreur détaillé
            const errorMsg = data.error ? `Erreur: ${data.error}` : 'Erreur lors de la mise à jour des informations.';
            console.error(errorMsg);
            alert(errorMsg);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la mise à jour des informations.');
    });
}

// Ajouter un écouteur d'événement pour le redimensionnement de la fenêtre
let resizeTimeout;
window.addEventListener('resize', function() {
    // Utiliser un délai pour éviter de redessiner trop souvent pendant le redimensionnement
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
        if (currentPdf) {
            renderPdf();
        }
    }, 200);
});

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer les éléments du DOM
    const tableViewBtn = document.getElementById('tableViewBtn');
    const tileViewBtn = document.getElementById('tileViewBtn');
    const tableView = document.getElementById('tableView');
    const tileView = document.getElementById('tileView');
    const mainContainer = document.getElementById('mainContainer');
    const candidatesContainer = document.getElementById('candidatesContainer');
    const pdfViewerContainer = document.querySelector('.w-full.md\\:w-1\\/2:not(#candidatesContainer)');
    
    // Récupérer les contrôles de zoom
    const zoomControls = document.getElementById('zoomControls');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    
    // Niveau de zoom initial (1 = 100%)
    let zoomLevel = 1;
    // Récupérer le niveau de zoom enregistré
    const savedZoom = localStorage.getItem('cvManagerZoomLevel');
    if (savedZoom) {
        zoomLevel = parseFloat(savedZoom);
    }
    
    // Fonction pour afficher la vue tableau (2 colonnes avec visualiseur PDF)
    function showTableView() {
        // Afficher la vue tableau et masquer la vue tuile
        tableView.classList.remove('hidden');
        tileView.classList.add('hidden');
        tableViewBtn.classList.add('active');
        tileViewBtn.classList.remove('active');
        localStorage.setItem('cvManagerViewMode', 'table');
        
        // Restaurer la mise en page à deux colonnes
        candidatesContainer.classList.remove('md:w-full');
        candidatesContainer.classList.add('md:w-1/2');
        pdfViewerContainer.classList.remove('hidden');
        
        // Masquer les contrôles de zoom
        zoomControls.classList.add('hidden');
        zoomControls.style.display = 'none';
        
        // S'assurer que la vue en tuile est complètement masquée
        tileView.style.display = 'none';
    }
    
    // Fonction pour afficher la vue en tuile (1 colonne pleine largeur sans visualiseur PDF)
    function showTileView() {
        // Afficher la vue tuile et masquer la vue tableau
        tableView.classList.add('hidden');
        tileView.classList.remove('hidden');
        tableViewBtn.classList.remove('active');
        tileViewBtn.classList.add('active');
        localStorage.setItem('cvManagerViewMode', 'tile');
        
        // Modifier la mise en page pour une seule colonne pleine largeur
        candidatesContainer.classList.remove('md:w-1/2');
        candidatesContainer.classList.add('md:w-full');
        pdfViewerContainer.classList.add('hidden');
        
        // Afficher les contrôles de zoom
        zoomControls.classList.remove('hidden');
        zoomControls.style.display = 'flex';
        
        // S'assurer que la vue en tuile est correctement affichée avec une grille responsive
        tileView.style.display = 'grid';
        tileView.style.gridTemplateColumns = 'repeat(auto-fill, minmax(300px, 1fr))';
        tileView.style.gap = '16px';
        tileView.style.justifyContent = 'center';
        
        // Charger les miniatures PDF quand on passe à la vue en tuile
        loadPdfThumbnails();
        
        // Ajuster la taille des aperçus PDF pour éviter les espaces vides
        adjustPdfThumbnailSize();
        
        // Appliquer le niveau de zoom actuel
        applyZoom();
    }
    
    // Fonction pour zoomer
    function zoomIn() {
        if (zoomLevel < 2) { // Limite maximale de zoom
            zoomLevel += 0.1;
            applyZoom();
            localStorage.setItem('cvManagerZoomLevel', zoomLevel.toString());
        }
    }
    
    // Fonction pour dézoomer
    function zoomOut() {
        if (zoomLevel > 0.5) { // Limite minimale de zoom
            zoomLevel -= 0.1;
            applyZoom();
            localStorage.setItem('cvManagerZoomLevel', zoomLevel.toString());
        }
    }
    
    // Fonction pour appliquer le zoom
    function applyZoom() {
        // Calculer les nouvelles dimensions en fonction du niveau de zoom
        const zoomedWidth = STANDARD_TILE_WIDTH * zoomLevel;
        const zoomedHeight = STANDARD_TILE_HEIGHT * zoomLevel;
        
        const tiles = document.querySelectorAll('.candidate-tile');
        tiles.forEach(tile => {
            // Récupérer le canvas et le conteneur de l'aperçu
            const canvas = tile.querySelector('.pdf-thumbnail');
            const previewContainer = tile.querySelector('.tile-pdf-preview');
            
            if (canvas && previewContainer) {
                // Appliquer le zoom au canvas dans les deux axes
                canvas.style.width = `${zoomedWidth}px`;
                canvas.style.height = `${zoomedHeight}px`;
                
                // Ajuster la taille du conteneur de l'aperçu
                previewContainer.style.width = `${zoomedWidth}px`;
                previewContainer.style.height = `${zoomedHeight}px`;
                
                // Ajuster la largeur de la tuile
                tile.style.width = `${zoomedWidth + 20}px`; // +20px pour les marges
            }
        });
        
        // Réorganiser les tuiles après le zoom
        const tileContainer = document.querySelector('.tile-container');
        if (tileContainer) {
            tileContainer.style.display = 'flex';
            tileContainer.style.flexWrap = 'wrap';
            tileContainer.style.justifyContent = 'space-around';
        }
    }
    
    // Fonction pour ajuster la taille des aperçus PDF
    function adjustPdfThumbnailSize() {
        // Attendre que les miniatures soient chargées
        setTimeout(() => {
            const thumbnails = document.querySelectorAll('.pdf-thumbnail');
            thumbnails.forEach(canvas => {
                // Conserver les proportions originales du PDF
                // Ne pas modifier la taille du canvas pour préserver les proportions
                
                // Ajuster la taille de la tuile en fonction de la taille du PDF
                const tile = canvas.closest('.candidate-tile');
                if (tile) {
                    // Définir une largeur fixe pour toutes les tuiles basée sur la largeur du PDF
                    const pdfWidth = canvas.width;
                    // Ajuster la largeur de la tuile pour qu'elle corresponde à la largeur du PDF
                    // avec une marge de 20px (10px de chaque côté)
                    tile.style.width = `${pdfWidth + 20}px`;
                    tile.style.margin = '0 auto 16px';
                }
            });
            
            // Réajuster la grille pour qu'elle s'adapte aux nouvelles tailles des tuiles
            const tileContainer = document.querySelector('.tile-container');
            if (tileContainer) {
                tileContainer.style.display = 'flex';
                tileContainer.style.flexWrap = 'wrap';
                tileContainer.style.justifyContent = 'space-around';
            }
        }, 500);
    }
    
    // Ajouter les écouteurs d'événements pour les boutons de basculement
    tableViewBtn.addEventListener('click', showTableView);
    tileViewBtn.addEventListener('click', showTileView);
    
    // Ajouter les écouteurs d'événements pour les boutons de zoom
    zoomInBtn.addEventListener('click', zoomIn);
    zoomOutBtn.addEventListener('click', zoomOut);
    
    // Vérifier si une préférence de vue est enregistrée
    const savedView = localStorage.getItem('cvManagerViewMode');
    if (savedView === 'tile') {
        showTileView();
    } else {
        showTableView();
    }
});
