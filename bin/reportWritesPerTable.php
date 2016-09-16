<?php
/*
This script is intended to give you table-level information about where writes are being performed
in your application.

It does this by remotely retrieving the binary log through the `mysqlbinlog` program, and parsing
the contents of the binary log to determine the database and table of each UPDATE, INSERT, ALTER,
REPLACE or DELETE statement

It goes through all available binary log files, summarizes each one and then presents the
combined information as well.
*/


$host = 'your-hostname-here';
$user = 'your-username-here';
$pass = 'your-password-here';
$port = 3306;

$hostName = "{$host}:{$port}";
$link = mysql_connect($hostName, $user, $pass)
    or die('Could not connect: ' . mysql_error());


$result = mysql_query('SHOW BINARY LOGS');

$summary = [];
$tmpfile = tempnam(sys_get_temp_dir(), 'foo');
$overallSummary = null;
$overallStartTimestamp = null;
$overallLineCount = 0;

$rowCount = mysql_num_rows($result);
$i = 0;

echo "Found {$rowCount} binary log files on master to parse\n\n";

while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $i++;

    $command = "mysqlbinlog --read-from-remote-server --host {$host} --user {$user} --password=\"{$pass}\" --base64-output=DECODE-ROWS --verbose {$row['Log_name']} -r {$tmpfile} 2> /dev/null";
    echo "\n{$i}/{$rowCount}.  Log={$row['Log_name']}\n";
    // echo " Executing {$command}\n";
    exec($command);

    $fh = fopen($tmpfile, 'r');
    $lineCount = 0;
    $fileSummary = [];
    $startTimestamp = null;
    while ($line = fgets($fh)) {
        if (preg_match('#SET TIMESTAMP=(\d+)#', $line, $matches)) {
            if (!$startTimestamp) {
                // echo "Found startTimestamp = {$startTimestamp} (".date('Y-m-d H:i:s', $startTimestamp).")\n";
                $startTimestamp = trim($matches[1]);
                if (!$overallStartTimestamp) {
                    $overallStartTimestamp = $startTimestamp;
                }
            }
            $lastTimestamp = trim($matches[1]);
        }
        $lineCount++;
        $function = null;
        $table    = null;
        if (preg_match('#(^update|^insert|^delete|^replace|^alter) `(.*?)`#i', $line, $matches)) {
            $database = 'DEFAULT';
            $function = strtolower(trim($matches[1]));
            $table    = strtolower(trim($matches[2]));
        }

        if (preg_match('/^### (update|insert|delete|replace|alter) `(.*?)`\.`(.*?)`/i', $line, $matches)) {
            $function = strtolower(trim($matches[1]));
            $database = strtolower(trim($matches[2]));
            $table    = strtolower(trim($matches[3]));
        }

        if ($function && $table) {
            if (!isset($fileSummary[$database][$table][$function])) {
                $fileSummary[$database][$table][$function] = 0;
            }
            $fileSummary[$database][$table][$function]++;
        }
    }
    $elapsed = $lastTimestamp - $startTimestamp;
    echo "Parsed ".number_format($lineCount)." lines spanning {$elapsed} seconds between ";
    echo date('Y-m-d H:i:s', $startTimestamp)." and ". date('Y-m-d H:i:s', $lastTimestamp)."\n";

    foreach ($fileSummary as $database => $tableDetail) {
        foreach ($tableDetail as $table => $actionDetail) {
            foreach ($actionDetail as $action => $count) {
                printf("%-20s %-20s %10s = %d\n", $database, $table, $action, $count);

                if (!isset($overallSummary[$database][$table][$action])) {
                    $overallSummary[$database][$table][$action] = 0;
                }
                $overallSummary[$database][$table][$action] += $count;
            }
        }
    }

    $overallLineCount += $lineCount;

}

unlink($tmpfile);

$overallElapsed = $lastTimestamp - $overallStartTimestamp;
echo "ALL DONE WITH ALL AVAILABLE BINARY LOGS\n";
echo "Parsed ".number_format($overallLineCount)." lines representing {$overallElapsed} seconds between";
echo date('Y-m-d H:i:s', $overallStartTimestamp)." and ". date('Y-m-d H:i:s', $lastTimestamp)."\n";
foreach ($overallSummary as $database => $tableDetail) {
    foreach ($tableDetail as $table => $actionDetail) {
        foreach ($actionDetail as $action => $count) {
            printf("%-20s %-20s %10s = %d\n", $database, $table, $action, $count);
        }
    }
}
