#!/bin/sh

# ----------------------------------------------------------
# @file
#   This script will auto-generate a demonstratie subtheme.
# Usage:
#   $ sh subtheme.sh "My Subtheme" my_subtheme
# ----------------------------------------------------------

# Check for arguments.
if [[ $# -ne 3 ]]
then
  # Declare variables from arguments.
  NAME=$1
  TARGET=$2
  # Copy Demonstratie Subtheme.
  cp -R demonstratie_sub $TARGET
  # Rename theme.
  perl -pi -w -e "s/Demonstratie Subtheme/$NAME/g;" $TARGET/*.info
  # Remove 'HIDDEN' declaration.
  perl -pi -w -e "s/hidden = TRUE//g;" $TARGET/*.info
  # Rename info file.
  mv $TARGET/*.info $TARGET/$TARGET.info
  # Friendly message.
  echo "-------------------------------------"
  echo "Subtheme \"$NAME\" created in $TARGET"
  echo "-------------------------------------"
fi
