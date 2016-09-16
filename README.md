# mysql-writes-per-table
Identify how many writes are done to each MySQL table

# Purpose
It can be difficult for an application developer to know where to focus their
efforts when attempting to improve efficiency of an application.  MySQL
provides metrics at the server-level, but very little more granular than that

This project attempts to provide more granular reporting, identifying how many
update/insert/replace/alter/delete statements are occurring on each table

# How it works

 - This script runs `SHOW BINARY LOGS` on the server
 - For each log file, it runs `mysqlbinlog` to retrieve the log file from the server and saves to a local file
 - It then reads through the contents of the log file and compiles information regarding tables that were written to
 - it writes a summary about each binary log file as well as a summary for all log files combined

# Configuration
Modify the following lines near the top of bin/reportWritesPerTable.php

```
$host = 'your-hostname-here';
$user = 'your-username-here';
$pass = 'your-password-here';
$port = 3306;
```

# Sample output:

```
16:51 $ php bin/reportWritesPerTable.php
Found 1160 binary log files on master to parse

1/1160.  Log=mysql-bin-changelog.231956
Parsed 2,552,183 lines spanning 300 seconds between 2016-09-12 15:15:00 and 2016-09-12 15:20:00
master               metrics                  update = 101855
DEFAULT              users                    update = 15
DEFAULT              accounts                 update = 384
DEFAULT              limits                   update = 29

2/1160.  Log=mysql-bin-changelog.231957
Parsed 1,105,725 lines spanning 301 seconds between 2016-09-12 15:19:58 and 2016-09-12 15:24:59
master               metrics                  update = 43944
DEFAULT              accounts                 update = 465
DEFAULT              users                    update = 8
DEFAULT              limits                   update = 55

3/1160.  Log=mysql-bin-changelog.231958
Parsed 276,551 lines spanning 300 seconds between 2016-09-12 15:25:00 and 2016-09-12 15:30:00
master               metrics                  update = 10880
DEFAULT              accounts                 update = 290
DEFAULT              limits                   update = 29
DEFAULT              users                    update = 17

..... lots more content was here ...

1160/1160.  Log=mysql-bin-changelog.233107
Parsed 436,720 lines spanning 300 seconds between 2016-09-16 15:00:00 and 2016-09-16 15:05:00
master               metrics                  update = 16892
DEFAULT              accounts                 update = 802
DEFAULT              users                    update = 7
DEFAULT              limits                   update = 42


ALL DONE WITH ALL AVAILABLE BINARY LOGS

Parsed 1,084,152,267 lines representing 345900 seconds
master               metrics                  update = 42662544
master               users                    update = 11
DEFAULT              accounts                 update = 744351
DEFAULT              users                    update = 8088
DEFAULT              limits                   update = 153692
DEFAULT              bundles                  update = 1
DEFAULT              offers                   update = 1
DEFAULT              settings                 update = 4
```

In this example, you can clearly see that the `metrics` table had the vast majority of writes according to the log files parsed over the previous 4 days (345900 seconds)


