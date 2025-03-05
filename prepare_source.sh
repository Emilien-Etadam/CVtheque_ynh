#!/bin/bash

# Ce script prépare les fichiers source de l'application pour l'intégration dans la recette YunoHost

# Répertoire source (application actuelle)
SOURCE_DIR=".."

# Répertoire de destination (dans la recette YunoHost)
DEST_DIR="./sources"

# Créer le répertoire de destination s'il n'existe pas
mkdir -p "$DEST_DIR"

# Copier les fichiers PHP et CSS
echo "Copie des fichiers PHP et CSS..."
cp "$SOURCE_DIR"/*.php "$DEST_DIR"/
cp "$SOURCE_DIR"/*.css "$DEST_DIR"/

# Copier le fichier LICENSE
echo "Copie du fichier LICENSE..."
cp "$SOURCE_DIR"/LICENSE "$DEST_DIR"/

# Créer les répertoires nécessaires
echo "Création des répertoires nécessaires..."
mkdir -p "$DEST_DIR/uploads"
mkdir -p "$DEST_DIR/temp"

# Créer un fichier .gitignore pour exclure les fichiers temporaires et les uploads
echo "Création du fichier .gitignore..."
cat > "$DEST_DIR/.gitignore" << EOF
# Fichiers de base de données
*.db
*.sqlite
*.sqlite3

# Répertoires d'uploads et temporaires
/uploads/*
/temp/*

# Fichiers système
.DS_Store
Thumbs.db
EOF

echo "Préparation des sources terminée !"
echo "Les fichiers ont été copiés dans $DEST_DIR"
