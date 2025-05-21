#!/bin/bash

DB_USER="root"
DB_PASS=""
DB_NAME="counterparty"
DB_HOST="localhost"

# Function to detect OS
detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    else
        echo "linux"
    fi
}

# Get OS type
OS_TYPE=$(detect_os)

# MySQL connection details - you can modify these


# Process each .sql.gz file
for file in *.sql.gz; do
    if [ -f "$file" ]; then
        echo "Processing $file..."
        
        # Extract the file
        gunzip "$file"
        
        # Get the base filename without .gz
        base_file="${file%.gz}"
        
        # Update character set and collation based on OS
        if [ "$OS_TYPE" = "macos" ]; then
            sed -i '' 's/utf8mb4_0900_ai_ci/utf8_general_ci/g' "$base_file"
            sed -i '' 's/CHARSET=utf8mb4/CHARSET=utf8/g' "$base_file"
        else
            sed -i 's/utf8mb4_0900_ai_ci/utf8_general_ci/g' "$base_file"
            sed -i 's/CHARSET=utf8mb4/CHARSET=utf8/g' "$base_file"
        fi
        
        # Import into MySQL
        echo "Importing $base_file into MySQL..."
        if [ -z "$DB_PASS" ]; then
            mysql -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" < "$base_file"
        else
            mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$base_file"
        fi
        
        if [ $? -eq 0 ]; then
            echo "Successfully imported $base_file"
        else
            echo "Error importing $base_file"
            exit 1
        fi
        
        echo "Completed processing $file"
    fi
done

echo "All files have been processed and imported successfully!" 