PhingTasks
==========

Tasks para o Phing que utilizo no dia a dia.

Instalação
------------
Via composer:

         {
              "require": {
                  "rochasmarcelo/phing-tasks": "dev-master",
                  "phing/phing": "dev-master"
              }
          }

Exemplos
------------
 1. **FTPDownloadTask**
 No seu projeto crie o arquivo config/project.properties com as configurações do servidor FTP e então copie o conteudo abaixo no arquivo build.xml:
```xml
 <?xml version="1.0" encoding="UTF-8" ?>
<project name="FtpDownload" default="example">
    <taskdef name="ftpdownload" classname="FtpDownloadTask" />
    <property file="config/project.properties" />
    <target name="example" description="An example of the task FtpDownload">
        <echo msg="An example of the task FtpDownload" />
        <ftpdownload
            host="${ftp.host}"
            port="${ftp.port}"
            username="${ftp.username}"
            password="${ftp.password}"
            mode="${ftp.mode}"
            passive="${ftp.passive}"
            dir="${ftp.dir}"
            loglevel="error"
            localDir="temp-dir/backup"
            propertyName="totalSuccess"
        >
          <filelist dir="/public_html/img" files="img1.png,img2.jpg,home.png" />
        </ftpdownload>
        <echo msg="Total files downloaded: ${totalSuccess}" />
    </target>
</project>
```
Agora execute:
        $ vendor/bin/phing
2. **SvnChangedFilesTask**
No seu projeto copie o conteudo abaixo no arquivo build.xml:
```xml
<?xml version="1.0" encoding="UTF-8" ?>
<project name="SvnChangedFiles" default="example">
    <property name="workingcopy" value="" />
    <taskdef name="svnchangedfiles" classname="SvnChangedFilesTask" />
    <target name="example" description="An example of the task SvnChangedFiles">
        <echo msg="An example of the task SvnChangedFiles" />
        <svnchangedfiles
           svnpath="/usr/bin/svn"
           username="yourname"
           password="yourpassword"
           nocache="true"
           workingcopy="/your/working/copy/path/"
           revisionRange="10:HEAD"
           forceRelativePath="true"
        />
        <foreach list="${svn.changed}" param="changedFile" target="show-changed-files" />

    </target>
    <target name="show-changed-files">
        <echo msg="${changedFile}" />
    </target>
</project>
```
Agora execute:
        $ vendor/bin/phing
