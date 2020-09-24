# Backup

This tutorial demonstrates commands for backing up RSSServer. It assumes that your main RSSServer directory is `/usr/share/RSSServer`; If you've installed it somewhere else, substitute your path as necessary.

## Installation Backup

Do this before an upgrade.

### Creating a Backup

First, Enter the directory you wish to save your backup to. Here, for example, we'll save the backup to the user home directory
```
cd ~
```

Next, we'll create a gzipped tar archive of the RSSServer directory. The following command will archive the entire contents of your RSSServer installation in it's current state.
```
tar -czf RSSServer-backup.tgz -C /usr/share/RSSServer/ .
```

And you're done!

### Restoring from a Backup

First, copy the backup previously made into your RSSServer directory
```
cp ~/RSSServer-backup.tgz /usr/share/RSSServer/
```

Next, change to your RSSServer directory
```
cd /usr/share/RSSServer/
```

Extract the backup
```
tar -xzf RSSServer-backup.tgz
```

And optionally, as cleanup, remove the copy of your backup from the RSSServer directory
```
rm RSSServer-backup.tgz
```

## Backing up Feeds

### Feed list Export
You can export your feed list in OPML format either from the web interface, or from the [Command-Line Interface].

### Saving Articles

To save articles, you can use [phpMyAdmin](https://www.phpmyadmin.net/) or MySQL tools, where `<db_user>` is your database username, `<db_host>` is the hostname of your web server containing your RSSServer database, and `<rssserver_db>` is the database used by RSSServer:
```
mysqldump --skip-comments --disable-keys --user=<db_user> --password --host <db_host> --result-file=rssserver.dump.sql --databases <rssserver_db>
```
