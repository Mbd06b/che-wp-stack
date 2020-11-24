<?php

/**
 * Open a connection via PDO, 
 * 1. If not already initalized, create the database from the .sql dump file.
 * 2. Get the siteurl from that database,
 * 3. if the WP_HOME is different than the 'siteurl',
 *    update the siteurl in the database. 
 */

require "db-init-config.php";

try {

    $connection = new PDO($dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    $connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    $databaseSiteUrl = null;
    
    $query = $connection->query("SELECT option_value FROM wp_options WHERE option_name = 'siteurl'");
    
    if(!$query){
        // use an sql dump of wordpress database, load in this directory.
        $res = importSqlFile($connection, 'null_sqldump.sql');
        if ($res === false) {
            die('ERROR');
        } else {
            echo "\n Database and table users created successfully.";  
            $query = $connection->query("SELECT option_value FROM wp_options WHERE option_name = 'siteurl'");
        }
    } else {
         echo "\n Database has already been initialized. ";
    }

    $result=$query->fetch();
    $databaseSiteUrl = $result["option_value"];
    echo "\n Siteurl in database is: " . $databaseSiteUrl;
    if($databaseSiteUrl === $workspaceUrl){
        echo "\n No URL change in .env detected!";
    } else {
        echo "\n .ENV UPDATE DETECTED!";
        $sth = $connection->prepare("UPDATE wp_options SET option_value = replace(option_value, :originUrl , :workspaceUrl ) WHERE option_name =  'home' OR option_name = 'siteurl';
                UPDATE wp_posts SET guid = replace(guid, :originUrl, :workspaceUrl );
                UPDATE wp_posts SET post_content = replace(post_content, :originUrl, :workspaceUrl );
                UPDATE wp_postmeta SET meta_value = replace(meta_value, :originUrl, :workspaceUrl );");

        $sth->bindParam(':originUrl', $databaseSiteUrl, PDO::PARAM_STR);
        $sth->bindParam(':workspaceUrl', $workspaceUrl, PDO::PARAM_STR);
        $sth->execute(); 
        echo "\n WP_HOME has been updated to: " . $workspaceUrl;
    }
       

} catch(PDOException $error) {
    echo $error->getMessage();
}


/**
 * Import SQL File
 * credit: https://bedigit.com/blog/import-mysql-large-database-sql-file-using-pdo/
 *
 * @param $pdo
 * @param $sqlFile
 * @param null $tablePrefix
 * @param null $InFilePath
 * @return bool
 */
function importSqlFile($pdo, $sqlFile, $tablePrefix = null, $InFilePath = null)
{
    try {
        
        // Enable LOAD LOCAL INFILE
        $pdo->setAttribute(\PDO::MYSQL_ATTR_LOCAL_INFILE, true);
        
        $errorDetect = false;
        
        // Temporary variable, used to store current query
        $tmpLine = '';
        
        // Read in entire file
        $lines = file($sqlFile);
        
        // Loop through each line
        foreach ($lines as $line) {
            // Skip it if it's a comment
            if (substr($line, 0, 2) == '--' || trim($line) == '') {
                continue;
            }
            
            // Read & replace prefix
            $line = str_replace(['<<prefix>>', '<<InFilePath>>'], [$tablePrefix, $InFilePath], $line);
            
            // Add this line to the current segment
            $tmpLine .= $line;
            
            // If it has a semicolon at the end, it's the end of the query
            if (substr(trim($line), -1, 1) == ';') {
                try {
                    // Perform the Query
                    $pdo->exec($tmpLine);
                } catch (\PDOException $e) {
                    echo "<br><pre>Error performing Query: '<strong>" . $tmpLine . "</strong>': " . $e->getMessage() . "</pre>\n";
                    $errorDetect = true;
                }
                
                // Reset temp variable to empty
                $tmpLine = '';
            }
        }
        
        // Check if error is detected
        if ($errorDetect) {
            return false;
        }
        
    } catch (\Exception $e) {
        echo "<br><pre>Exception => " . $e->getMessage() . "</pre>\n";
        return false;
    }
    
    return true;
}