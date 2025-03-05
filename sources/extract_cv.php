<?php
/**
 * Module d'extraction d'informations des CV
 * 
 * Ce fichier contient les fonctions permettant d'extraire des informations
 * structurées à partir de fichiers PDF de CV. Il utilise des expressions régulières
 * pour identifier les informations importantes comme le nom et l'email.
 * 
 * @version 1.0
 */

/**
 * Extrait le texte d'un fichier PDF
 * 
 * Cette fonction utilise plusieurs méthodes d'extraction pour maximiser
 * les chances d'obtenir du texte lisible à partir d'un PDF.
 * 
 * @param string $pdfPath Chemin vers le fichier PDF
 * @return string Texte extrait du PDF
 */
function extractTextFromPdf($pdfPath) {
    // Vérifier si le fichier existe
    if (!file_exists($pdfPath)) {
        error_log("Fichier PDF non trouvé: $pdfPath");
        return '';
    }
    
    // Récupérer le contenu brut du PDF
    $content = file_get_contents($pdfPath);
    $text = '';
    
    // Méthode 1: Extraction de texte entre parenthèses
    // Cette méthode fonctionne bien pour de nombreux PDF où le texte est stocké entre parenthèses
    $pattern = "/(\(([^)]+)\))/";
    if (preg_match_all($pattern, $content, $matches)) {
        foreach ($matches[2] as $match) {
            // Filtrer les caractères non imprimables et les caractères de contrôle
            if (preg_match("/^[\\w\\s,.;:!?'\"-@]+$/u", $match)) {
                $text .= $match . ' ';
            }
        }
    }
    
    // Méthode 2: Extraction de texte entre crochets
    // Certains PDF stockent le texte entre crochets
    $pattern2 = "/(\[([^\]]+)\])/";
    if (preg_match_all($pattern2, $content, $matches)) {
        foreach ($matches[2] as $match) {
            if (preg_match("/^[\\w\\s,.;:!?'\"-@]+$/u", $match)) {
                $text .= $match . ' ';
            }
        }
    }
    
    // Méthode 3: Extraction de texte entre balises TJ
    // Les balises TJ sont utilisées dans le format PDF pour définir du texte
    $pattern3 = "/TJ\s*\[(.*?)\]/s";
    if (preg_match_all($pattern3, $content, $matches)) {
        foreach ($matches[1] as $match) {
            $text .= $match . ' ';
        }
    }
    
    // Méthode 4: Recherche directe d'emails
    // Cette méthode est spécifique pour trouver des adresses email
    $emailPattern = "/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/";
    if (preg_match_all($emailPattern, $content, $matches)) {
        foreach ($matches[0] as $match) {
            $text .= ' ' . $match . ' ';
        }
    }
    
    // Journaliser un extrait du texte extrait pour le débogage
    $textLength = strlen($text);
    $textPreview = substr($text, 0, 100) . ($textLength > 100 ? "..." : "");
    error_log("Texte extrait du PDF (longueur: $textLength): $textPreview");
    
    return $text;
}

/**
 * Extrait le nom et prénom d'un texte de CV
 * 
 * @param string $text Texte du CV
 * @param string $fileName Nom du fichier (utilisé comme fallback)
 * @return string Nom et prénom extraits
 */
function extractName($text, $fileName = '') {
    // Nettoyer le texte pour faciliter l'extraction
    $cleanText = str_replace(["\r", "\n", "\t"], ' ', $text);
    $cleanText = preg_replace('/\s+/', ' ', $cleanText);
    
    // Méthode 1: Rechercher les patterns de nom et prénom au début du CV
    $namePatterns = [
        // Prénom Nom au début du document
        '/^\s*([A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+\s+[A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+)/u',
        
        // Nom: Prénom Nom
        '/Nom\s*:?\s*([A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+\s+[A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+)/ui',
        
        // Prénom: X, Nom: Y
        '/Pr[ée]nom\s*:?\s*([A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+).*?Nom\s*:?\s*([A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+)/ui',
        
        // Après "CV de" ou "Curriculum Vitae de"
        '/(?:CV|Curriculum Vitae)\s+(?:de|of|du|pour)\s+([A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+\s+[A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+)/ui',
        
        // Nom en majuscules suivi du prénom
        '/([A-ZÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑÇČŠŽ\'\-]+)\s+([A-Z][a-zàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ\'\-]+)/u'
    ];
    
    foreach ($namePatterns as $pattern) {
        if (preg_match($pattern, $cleanText, $matches)) {
            // Si le pattern contient deux groupes de capture (prénom et nom séparés)
            if (isset($matches[2])) {
                return $matches[1] . ' ' . $matches[2];
            }
            // Sinon, utiliser le premier groupe de capture (prénom et nom ensemble)
            else if (isset($matches[1])) {
                $name = trim($matches[1]);
                if (strlen($name) > 0 && strlen($name) < 50) {
                    return $name;
                }
            }
        }
    }
    
    // Méthode 2: Rechercher les sections courantes où le nom pourrait apparaître
    $nameSections = [
        'Curriculum Vitae', 'CV', 'Resume', 'Résumé'
    ];
    
    foreach ($nameSections as $section) {
        if (preg_match('/'. $section .'\s*:?\s*(.+?)(?:\s|$)/i', $cleanText, $matches)) {
            $name = trim($matches[1]);
            if (strlen($name) > 0 && strlen($name) < 50) {
                return $name;
            }
        }
    }
    
    // Méthode 3: Extraire à partir du nom du fichier si c'est un format standard (Nom_Prénom)
    if (!empty($fileName)) {
        $fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
        
        // Format ROCHE_ARTHUR_CV_2025.pdf -> Arthur Roche
        if (preg_match('/^([A-Z]+)_([A-Z]+)/', $fileNameWithoutExt, $matches)) {
            $lastName = ucfirst(strtolower($matches[1]));
            $firstName = ucfirst(strtolower($matches[2]));
            return $firstName . ' ' . $lastName;
        }
        
        // Format standard
        $name = str_replace('_', ' ', $fileNameWithoutExt);
        $name = preg_replace('/\s*CV\s*|\d+/', '', $name); // Supprimer "CV" et les chiffres
        $name = preg_replace('/\s+/', ' ', $name); // Normaliser les espaces
        
        return trim($name);
    }
    
    return '';
}

/**
 * Extrait l'email d'un texte de CV
 * 
 * @param string $text Texte du CV
 * @return string Email extrait
 */
function extractEmail($text) {
    // Nettoyer le texte pour faciliter l'extraction
    $cleanText = str_replace(["\r", "\n", "\t"], ' ', $text);
    $cleanText = preg_replace('/\s+/', ' ', $cleanText);
    
    // Rechercher des patterns d'email courants
    $patterns = [
        // Pattern standard d'email
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/',
        
        // Pattern avec "Email:" ou "E-mail:" ou "Mail:" devant
        '/(?:E[-\s]?mail|Mail|Courriel|Contact)\s*:?\s*([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/i',
        
        // Pattern avec des caractères spéciaux ou espaces qui pourraient être dans le texte
        '/\b[A-Za-z0-9._%+-]+\s*@\s*[A-Za-z0-9.-]+\s*\.\s*[A-Za-z]{2,}\b/',
        
        // Pattern pour les emails avec des caractères accentués
        '/\b[A-Za-z0-9._%+-àáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšž]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/ui',
        
        // Pattern pour les emails avec des caractères spéciaux dans le domaine
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-àáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšž]+\.[A-Za-z]{2,}\b/ui'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $cleanText, $matches)) {
            // Si le pattern contient un groupe de capture, utiliser le premier groupe
            $email = isset($matches[1]) ? $matches[1] : $matches[0];
            
            // Nettoyer l'email des espaces ou caractères indésirables
            $email = preg_replace('/\s+/', '', $email);
            
            // Vérifier que l'email est valide
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }
    }
    
    // Rechercher des motifs qui pourraient être des emails mais avec des caractères spéciaux
    if (preg_match('/\b([A-Za-z0-9._%+-]+)\s*[\[\(]?at[\]\)]?\s*([A-Za-z0-9.-]+)\s*[\[\(]?dot[\]\)]?\s*([A-Za-z]{2,})\b/i', $cleanText, $matches)) {
        $email = $matches[1] . '@' . $matches[2] . '.' . $matches[3];
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }
    }
    
    return '';
}

/**
 * Extrait les informations de base d'un CV
 * 
 * @param string $pdfPath Chemin vers le fichier PDF
 * @param string $fileName Nom du fichier original
 * @return array Tableau associatif des informations extraites
 */
function extractCvInfo($pdfPath, $fileName = '') {
    error_log("Début de l'extraction des informations du CV: $pdfPath, $fileName");
    
    // Extraction du texte
    $text = extractTextFromPdf($pdfPath);
    
    // Extraction des informations
    $name = extractName($text, $fileName);
    error_log("Nom extrait: $name");
    
    $email = extractEmail($text);
    error_log("Email extrait: $email");
    
    // Extraction directe d'email à partir du contenu brut du PDF
    if (empty($email)) {
        $content = file_get_contents($pdfPath);
        $emailPattern = "/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/";
        if (preg_match($emailPattern, $content, $matches)) {
            $email = $matches[0];
            error_log("Email extrait directement du contenu brut: $email");
        }
    }
    
    // Si l'email n'a toujours pas été trouvé, essayer d'extraire à partir du nom du fichier
    if (empty($email)) {
        $fileNameWithoutExt = pathinfo($fileName, PATHINFO_FILENAME);
        if (preg_match('/([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/', $fileNameWithoutExt, $matches)) {
            $email = $matches[1];
            error_log("Email extrait du nom de fichier: $email");
        }
    }
    
    // Ne pas créer d'email fictif, laisser vide pour que l'utilisateur puisse le saisir manuellement
    error_log("Fin de l'extraction des informations du CV. Nom: $name, Email: $email");
    
    return [
        'name' => $name,
        'email' => $email
    ];
}
?>
