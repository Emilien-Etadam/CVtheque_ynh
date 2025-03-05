<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Gestionnaire de CV</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Application de gestion de CV et de candidatures">
    <!-- Feuilles de style -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="darkmode.css" rel="stylesheet">
    <!-- Scripts externes -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        .pdf-container {
            width: 100%;
            height: calc(100vh - 300px);
            min-height: 400px;
            background-color: #525659;
            overflow: auto;
            padding: 10px;
        }
        .pdf-page {
            margin: 0 auto 10px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
            background-color: white;
            /* Format A4 avec un ratio de 1:1.414 (largeur:hauteur) */
            max-width: 100%;
            display: block;
        }
        .sticky-container {
            position: sticky;
            top: 20px;
            height: calc(100vh - 180px);
            overflow: auto;
        }
        /* Ajustement pour les écrans plus petits */
        @media (max-width: 768px) {
            .pdf-container {
                height: 400px;
                min-height: 400px;
            }
            .sticky-container {
                height: 600px;
                overflow: auto;
            }
        }
    </style>
</head>
<body class="bg-gray-100 p-4" data-theme="dark">
    <div class="max-w-full mx-auto">
        <div class="relative">
            <h1 class="text-3xl font-bold mb-8">Gestionnaire de CV</h1>
            <button id="themeToggle" type="button" aria-label="Changer de thème">
                <svg id="moonIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
                <svg id="sunIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 hidden">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
            </button>
        </div>
        
        <div class="flex flex-col md:flex-row gap-2">
            <!-- Liste des candidats (colonne gauche) -->
            <div class="w-full md:w-1/2 bg-white p-3 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Liste des candidats</h2>
                
                <!-- Formulaire de filtrage -->
                <div class="mb-3 bg-gray-50 p-2 rounded-lg">
                    <h3 class="text-lg font-medium mb-2">Filtres</h3>
                    <form action="" method="get" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select name="filter_status" class="w-full p-2 border rounded">
                                <option value="">Tous les statuts</option>
                                <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status ?>" <?= isset($_GET['filter_status']) && $_GET['filter_status'] === $status ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Poste</label>
                            <select name="filter_position" class="w-full p-2 border rounded">
                                <option value="">Tous les postes</option>
                                <?php foreach ($positions as $position): ?>
                                <option value="<?= $position ?>" <?= isset($_GET['filter_position']) && $_GET['filter_position'] === $position ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($position) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2 flex justify-between">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                Filtrer
                            </button>
                            <a href="index.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                                Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-2 text-left">Nom</th>
                                <th class="p-2 text-left">Poste</th>
                                <th class="p-2 text-left">Statut</th>
                                <th class="p-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($candidates): while ($candidate = $candidates->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr class="border-t candidate-row" data-id="<?= $candidate['id'] ?>">
                                <td class="p-2">
                                    <span class="candidate-info"><?= htmlspecialchars($candidate['name']) ?></span>
                                    <input type="text" class="candidate-edit p-1 border rounded text-sm w-full hidden" 
                                           value="<?= htmlspecialchars($candidate['name']) ?>" data-field="name">
                                </td>
                                <td class="p-2">
                                    <span class="candidate-info"><?= htmlspecialchars($candidate['position']) ?></span>
                                    <input type="text" class="candidate-edit p-1 border rounded text-sm w-full hidden" 
                                           value="<?= htmlspecialchars($candidate['position']) ?>" data-field="position">
                                </td>
                                <td class="p-2">
                                    <form action="" method="post" class="inline">
                                        <input type="hidden" name="candidate_id" value="<?= $candidate['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" 
                                                class="p-1 border rounded text-sm <?php
                                                echo match($candidate['status']) {
                                                    'Accepté' => 'bg-green-100',
                                                    'Refusé' => 'bg-red-100',
                                                    'En cours' => 'bg-yellow-100',
                                                    default => ''
                                                }; ?>">
                                            <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status ?>" <?= $candidate['status'] === $status ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($status) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td class="p-2 text-center">
                                    <!-- Icône Voir CV -->
                                    <a href="javascript:void(0)" 
                                       onclick="showPdf('<?= htmlspecialchars($candidate['cv_path']) ?>', '<?= htmlspecialchars($candidate['name']) ?>')" 
                                       class="text-blue-500 hover:text-blue-700 mx-1" title="Voir CV">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    
                                    <!-- Icône Éditer -->
                                    <a href="javascript:void(0)" onclick="toggleEdit(<?= $candidate['id'] ?>)" 
                                       class="text-blue-500 hover:text-blue-700 mx-1 edit-toggle" title="Éditer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    
                                    <!-- Icône Supprimer -->
                                    <a href="?delete=<?= $candidate['id'] ?>" class="text-red-500 hover:text-red-700 mx-1" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce candidat ?')" title="Supprimer">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </a>
                                    
                                    <!-- Boutons de sauvegarde et d'annulation -->
                                    <button type="button" onclick="saveCandidate(<?= $candidate['id'] ?>)" 
                                            class="px-2 py-1 bg-green-500 text-white rounded text-xs mx-1 hidden save-button">
                                        Enregistrer
                                    </button>
                                    <button type="button" onclick="cancelEdit(<?= $candidate['id'] ?>)" 
                                            class="px-2 py-1 bg-gray-500 text-white rounded text-xs mx-1 hidden cancel-button">
                                        Annuler
                                    </button>
                                </td>
                            </tr>
                            <tr class="border-t">
                                <td colspan="4" class="p-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- Colonne 1: Email et Date -->
                                        <div class="candidate-row" data-id="<?= $candidate['id'] ?>">
                                            <div class="text-xs text-gray-500 mb-1">
                                                <span class="candidate-info">Email: <?= htmlspecialchars($candidate['email']) ?></span>
                                                <div class="candidate-edit hidden">
                                                    <label class="block text-xs font-medium text-gray-700">Email:</label>
                                                    <input type="email" class="p-1 border rounded text-sm w-full" 
                                                           value="<?= htmlspecialchars($candidate['email']) ?>" data-field="email">
                                                </div>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <span class="candidate-info">Date: <?= htmlspecialchars($candidate['application_date']) ?></span>
                                                <div class="candidate-edit hidden">
                                                    <label class="block text-xs font-medium text-gray-700">Date:</label>
                                                    <input type="date" class="p-1 border rounded text-sm w-full" 
                                                           value="<?= htmlspecialchars($candidate['application_date']) ?>" data-field="application_date">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Colonne 2: Commentaires -->
                                        <div class="candidate-row" data-id="<?= $candidate['id'] ?>">
                                            <div class="text-xs text-gray-500">
                                                <span class="candidate-info">Commentaires: <?= nl2br(htmlspecialchars($candidate['comments'])) ?></span>
                                                <div class="candidate-edit hidden">
                                                    <label class="block text-xs font-medium text-gray-700">Commentaires:</label>
                                                    <textarea class="p-1 border rounded text-sm w-full" 
                                                              data-field="comments"><?= htmlspecialchars($candidate['comments']) ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Visualiseur de PDF (colonne droite) -->
            <div class="w-full md:w-1/2">
                <div class="sticky-container">
                    <!-- Ajouter un candidat (en haut) -->
                    <div class="bg-white p-3 rounded-lg shadow-md mb-4">
                        <h2 class="text-xl font-semibold mb-2 text-center">Ajouter un candidat</h2>
                        <p class="mb-2 text-gray-600 text-center text-sm">Glissez et déposez un CV au format PDF ou cliquez pour sélectionner un fichier.</p>
                        
                        <form action="upload_pdf.php" method="post" enctype="multipart/form-data" class="space-y-2">
                            <div id="drop-area" class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer">
                                <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" class="hidden" required>
                                <label for="pdf_file" class="cursor-pointer">
                                    <div class="text-gray-500 mb-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <p class="text-xs">Glissez et déposez un fichier PDF ici ou cliquez pour sélectionner</p>
                                    </div>
                                    <p id="file-name" class="text-xs text-blue-500 mt-1 hidden"></p>
                                </label>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Visualiseur de PDF -->
                    <div id="pdfContainer" class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div id="pdfViewer" class="pdf-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configurer le chemin du worker PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        // Gestion du thème sombre
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const moonIcon = document.getElementById('moonIcon');
            const sunIcon = document.getElementById('sunIcon');
            
            // Définir le thème par défaut comme sombre
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.body.setAttribute('data-theme', savedTheme);
                updateThemeIcons(savedTheme);
            } else {
                // Si aucun thème n'est enregistré, utiliser le mode sombre par défaut
                localStorage.setItem('theme', 'dark');
                updateThemeIcons('dark');
            }
            
            // Fonction pour mettre à jour les icônes en fonction du thème
            function updateThemeIcons(theme) {
                if (theme === 'dark') {
                    moonIcon.classList.add('hidden');
                    sunIcon.classList.remove('hidden');
                } else {
                    moonIcon.classList.remove('hidden');
                    sunIcon.classList.add('hidden');
                }
            }
            
            // Gérer le clic sur le bouton de changement de thème
            themeToggle.addEventListener('click', function() {
                const currentTheme = document.body.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.body.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                updateThemeIcons(newTheme);
            });
        });
        
        // Fonctionnalité de drag and drop
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('pdf_file');
        const fileName = document.getElementById('file-name');
        
        // Empêcher le comportement par défaut (ouverture du fichier dans le navigateur)
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Ajouter des effets visuels pendant le drag
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('bg-blue-50');
            dropArea.classList.add('border-blue-300');
        }
        
        function unhighlight() {
            dropArea.classList.remove('bg-blue-50');
            dropArea.classList.remove('border-blue-300');
        }
        
        // Gérer le drop
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0 && files[0].type === 'application/pdf') {
                fileInput.files = files;
                
                // Afficher le nom du fichier
                fileName.textContent = files[0].name;
                fileName.classList.remove('hidden');
                
                // Soumettre automatiquement le formulaire
                setTimeout(() => {
                    dropArea.closest('form').submit();
                }, 500);
            } else {
                alert('Veuillez déposer un fichier PDF.');
            }
        }
        
        // Afficher le nom du fichier sélectionné
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                fileName.textContent = e.target.files[0].name;
                fileName.classList.remove('hidden');
                
                // Soumettre automatiquement le formulaire
                setTimeout(() => {
                    dropArea.closest('form').submit();
                }, 500);
            }
        });
        
        // Fonctions pour l'édition des informations des candidats
        function toggleEdit(candidateId) {
            const rows = document.querySelectorAll(`.candidate-row[data-id="${candidateId}"]`);
            
            rows.forEach(row => {
                // Masquer les informations et afficher les champs d'édition
                row.querySelectorAll('.candidate-info').forEach(info => {
                    info.classList.add('hidden');
                });
                
                row.querySelectorAll('.candidate-edit').forEach(edit => {
                    edit.classList.remove('hidden');
                });
            });
            
            // Masquer le bouton d'édition et afficher les boutons de sauvegarde et d'annulation
            const editToggle = document.querySelector(`.candidate-row[data-id="${candidateId}"] .edit-toggle`);
            const saveButton = document.querySelector(`.candidate-row[data-id="${candidateId}"] .save-button`);
            const cancelButton = document.querySelector(`.candidate-row[data-id="${candidateId}"] .cancel-button`);
            
            if (editToggle) editToggle.classList.add('hidden');
            if (saveButton) saveButton.classList.remove('hidden');
            if (cancelButton) cancelButton.classList.remove('hidden');
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
        
        // Ajouter un sélecteur personnalisé pour :contains
        jQuery.expr[':'].contains = function(a, i, m) {
            return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
        };
    </script>
</body>
</html>
