#!/bin/bash

# Script to fix the CV Manager database issues
# This script should be run on the server where CV Manager is installed

# Define colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}CV Manager Database Fix Script${NC}"
echo "This script will fix the database issues with your CV Manager installation."
echo ""

# Check if we're running as root
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Please run this script as root or with sudo.${NC}"
  exit 1
fi

# Default installation path
DEFAULT_PATH="/var/www/cv-manager"

# Ask for the installation path
read -p "Enter the CV Manager installation path [$DEFAULT_PATH]: " INSTALL_PATH
INSTALL_PATH=${INSTALL_PATH:-$DEFAULT_PATH}

# Check if the path exists
if [ ! -d "$INSTALL_PATH" ]; then
  echo -e "${RED}Error: The directory $INSTALL_PATH does not exist.${NC}"
  exit 1
fi

# Copy the initialization script to the installation directory
echo -e "${YELLOW}Copying initialization script to $INSTALL_PATH...${NC}"
cp init_database.php "$INSTALL_PATH/"

# Set proper permissions
echo -e "${YELLOW}Setting proper permissions...${NC}"
chown www-data:www-data "$INSTALL_PATH/init_database.php"
chmod 755 "$INSTALL_PATH/init_database.php"

# Navigate to the installation directory
cd "$INSTALL_PATH"

# Run the initialization script
echo -e "${YELLOW}Running database initialization script...${NC}"
php init_database.php

# Check if the script ran successfully
if [ $? -eq 0 ]; then
  echo -e "${GREEN}Database initialization completed successfully!${NC}"
  
  # Set proper permissions for the database file
  echo -e "${YELLOW}Setting proper permissions for the database file...${NC}"
  chown www-data:www-data "$INSTALL_PATH/cv.db"
  chmod 664 "$INSTALL_PATH/cv.db"
  
  # Set proper permissions for the uploads directory
  echo -e "${YELLOW}Setting proper permissions for the uploads directory...${NC}"
  if [ -d "$INSTALL_PATH/uploads" ]; then
    chown -R www-data:www-data "$INSTALL_PATH/uploads"
    chmod -R 775 "$INSTALL_PATH/uploads"
  fi
  
  echo -e "${GREEN}All done! Your CV Manager should now work correctly.${NC}"
  echo "You can access it at your domain/cv-manager"
else
  echo -e "${RED}Error: Database initialization failed.${NC}"
  echo "Please check the error messages above for more information."
  exit 1
fi

# Clean up
echo -e "${YELLOW}Cleaning up...${NC}"
rm "$INSTALL_PATH/init_database.php"

echo -e "${GREEN}Fix completed!${NC}"
