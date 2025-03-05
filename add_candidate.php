
<?php
/**
 * Ajout d'un candidat
 * 
 * Ce script gère l'ajout d'un nouveau candidat à partir des informations
 * extraites d'un CV PDF. Il permet de vérifier et compléter les informations
 * avant de les enregistrer dans la base de données.
 * 
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session pour récupérer les informations extraites
session_start();

// Vérifier si les informations extraites sont disponibles
if (!isset($_SESSION['extracted_info']) || !isset($_SESSION['temp_pdf_path'])) {
    // Rediriger vers la page d'upload si aucune information n'est disponible
    header('Location: upload_pdf.php');
    exit;
}

// Récupérer les informations extraites de la session
$extractedInfo = $_SESSION['extracted_info'];
$tempPdfPath = $_SESSION['temp_pdf_path'];
$originalFileName = $_SESSION['original_file_name'];

// Connexion à la base de données
try {
    $db = new SQLite3('cv.db');
    
    // Constantes de l'application
    $statuses = ['Nouveau', 'En cours', 'Entretien planifié', 'Accepté', 'Refusé'];
    $uploadDir = 'uploads/';
    $webUploadDir = 'uploads/';
    
    // Créer le répertoire d'upload s'il n'existe pas
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Récupération des postes existants pour le menu déroulant
    $positions = [];
    $positionsQuery = $db->query('SELECT DISTINCT position FROM candidates WHERE position != "" ORDER BY position');
    while ($position = $positionsQuery->fetchArray(SQLITE3_ASSOC)) {
        if (!empty($position['position'])) {
            $positions[] = $position['position'];
        }
    }
    
    /**
     * Traitement du formulaire d'ajout
     * Lorsque le formulaire est soumis, les informations sont enregistrées
     * et le CV est déplacé du répertoire temporaire vers le répertoire final
     */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Déterminer le poste (standard ou personnalisé)
        $position = $_POST['position'];
        if ($position === 'autre' && !empty($_POST['other_position'])) {
            $position = $_POST['other_position'];
        }
        
        // Générer un nom de fichier unique basé sur le nom du candidat et la date
        $fileName = $_POST['name'] . '_' . date('Y-m-d') . '_' . time();
        $fileExt = 'pdf'; // On sait que c'est un PDF
        $cvPath = $uploadDir . $fileName . '.' . $fileExt;
        $webPath = $webUploadDir . $fileName . '.' . $fileExt;
        
        // Vérifier si l'email existe déjà dans la base de données
        $email = $_POST['email'];
        $checkStmt = $db->prepare('SELECT id, name FROM candidates WHERE email = :email LIMIT 1');
        $checkStmt->bindValue(':email', $email);
        $checkResult = $checkStmt->execute();
        $existingCandidate = $checkResult->fetchArray(SQLITE3_ASSOC);
        
        if ($existingCandidate) {
            // Afficher un message d'erreur
            $error = "Un candidat avec cet email existe déjà : " . htmlspecialchars($existingCandidate['name']);
        } else {
            // Copier le fichier temporaire vers le répertoire final
            if (copy($tempPdfPath, $cvPath)) {
                // Insérer les informations du candidat dans la base de données
                $stmt = $db->prepare('INSERT INTO candidates 
                    (name, email, position, cv_path, application_date, comments, status) 
                    VALUES (:name, :email, :position, :cv_path, :date, :comments, "Nouveau")');
            
            $stmt->bindValue(':name', $_POST['name']);
            $stmt->bindValue(':email', $_POST['email']);
            $stmt->bindValue(':position', $position);
            $stmt->bindValue(':cv_path', $webPath);
            $stmt->bindValue(':date', $_POST['application_date']);
            $stmt->bindValue(':comments', $_POST['comments']);
            
            $stmt->execute();
            
                // Nettoyer : supprimer le fichier temporaire et les données de session
                unlink($tempPdfPath);
                unset($_SESSION['extracted_info']);
                unset($_SESSION['temp_pdf_path']);
                unset($_SESSION['original_file_name']);
                
                // Rediriger vers la page principale
                header('Location: index.php');
                exit;
            }
        }
    }
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ajouter un candidat - Gestionnaire de CV</title>
    <meta charset="UTF-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        .pdf-container {
            width: 100%;
            height: 500px;
            background-color: #525659;
            overflow: auto;
        }
        .pdf-page {
            margin: 10px auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            background-color: white;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Gestionnaire de CV - Ajouter un candidat</h1>
        
        <div class="flex flex-col md:flex-row gap-8">
            <!-- Formulaire d'ajout (colonne gauche) -->
            <div class="w-full md:w-1/2">
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-semibold mb-4">Étape 2 : Vérifier et compléter les informations</h2>
                    <p class="mb-4 text-gray-600">Les informations ont été extraites automatiquement du CV. Vérifiez-les et complétez si nécessaire.</p>
                    
                    <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                        <p><?= $error ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <form action="" method="post" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($extractedInfo['name']) ?>" class="w-full p-2 border rounded" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($extractedInfo['email']) ?>" class="w-full p-2 border rounded" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Poste</label>
                            <select name="position" id="position-select" class="w-full p-2 border rounded" required>
                                <option value="">Sélectionnez un poste</option>
                                <?php foreach ($positions as $position): ?>
                                <option value="<?= htmlspecialchars($position) ?>"><?= htmlspecialchars($position) ?></option>
                                <?php endforeach; ?>
                                <option value="autre">Autre (nouveau poste)</option>
                            </select>
                            <input type="text" id="other-position" name="other_position" placeholder="Spécifiez le poste" class="w-full p-2 border rounded mt-2 hidden">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date de candidature</label>
                            <input type="date" name="application_date" value="<?= date('Y-m-d') ?>" class="w-full p-2 border rounded" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Commentaires</label>
                            <textarea name="comments" class="w-full p-2 border rounded h-24"></textarea>
                        </div>
                        
                        <div class="flex justify-between">
                            <a href="upload_pdf.php" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                Retour
                            </a>
                            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Ajouter le candidat
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Prévisualisation du PDF (colonne droite) -->
            <div class="w-full md:w-1/2">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold mb-4">Prévisualisation du CV</h2>
                    <div id="pdfViewer" class="pdf-container"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Configurer le chemin du worker PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        // Gestion du champ "Autre poste"
        document.getElementById('position-select').addEventListener('change', function() {
            const otherField = document.getElementById('other-position');
            if (this.value === 'autre') {
                otherField.classList.remove('hidden');
                otherField.setAttribute('required', 'required');
            } else {
                otherField.classList.add('hidden');
                otherField.removeAttribute('required');
            }
        });
        
        // Fonction pour afficher le PDF
        async function showPdf(pdfPath) {
            const container = document.getElementById('pdfViewer');
            container.innerHTML = ''; // Vider le conteneur
            
            try {
                // Charger le document PDF
                const loadingTask = pdfjsLib.getDocument(pdfPath);
                const pdf = await loadingTask.promise;
                
                // Afficher les pages du PDF
                const numPages = pdf.numPages;
                for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                    const page = await pdf.getPage(pageNum);
                    
                    // Calculer l'échelle pour que la page s'adapte à la largeur du conteneur
                    const viewport = page.getViewport({ scale: 1 });
                    const containerWidth = container.clientWidth - 20; // Marge de 10px de chaque côté
                    const scale = containerWidth / viewport.width;
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
            } catch (error) {
                console.error('Erreur lors du chargement du PDF:', error);
                container.innerHTML = '<div class="p-4 text-red-500">Erreur lors du chargement du PDF</div>';
            }
        }
        
        // Afficher le PDF au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            showPdf('<?= $tempPdfPath ?>');
        });
    </script>
</body>
</html>
