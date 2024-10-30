=== KDisk ===
Contributors: kdiskplugin
Tags:  drive, kdisk, cloud, disk, files, share, диск, облако, файлы
Requires at least: 5.8
Tested up to: 6.1
Requires PHP: 7.0
Stable tag: 1.0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Cloud storage of photos, videos, documents and other files for each user. There is a shared disk for all visitors.


== Description ==

Плагин создаёт на Вашем сайте файловое хранилище (облако). Каждый зарегистрированный пользователь может загружать, удалять, скачивать, просматривать свои файлы.
Так же есть общее хранилище для всех посетителей. Настройка доступа к общему хранилищу по ролям. 
Если на сервере установлена программа ffmpeg:
- у видео файлов появляется иконка с кадром из фильма
- есть возможность конвертации видео в формат h264


The plugin creates a file storage (cloud) on your website. Each registered user can upload, delete, download, view their files.
There is also a common storage for all visitors. Configuring access to shared storage by role.
If ffmpeg is installed on the server:
- video files have an icon with a frame from the movie
- it is possible to convert video to h264 format

== Installation ==

1. Copy the plugin folder <strong> kdisk </strong> to <strong>/wp-content/plugins/ </strong>.
2. Activate the plugin via the <strong> Plugins </strong> menu.
3. URL to disk: mysite/kdisk/

1. Скопируйте папку плагина <strong>kdisk</strong> в <strong>/wp-content/plugins/</strong>. 
2. Активируйте плагин через меню <strong>Плагины</strong>.
3. URL к диску мойсайт/kdisk/

== Frequently Asked Questions ==

= Почему нет кадра из видео файла? =

Проверте установлена ли на сервере программа ffmpeg

== Screenshots ==

1. Пример папки с файлами
2. Процесс конвертации видео файла используя ffmpeg
3. Настройка доступа к общей папке
3. Properties file

== Changelog ==

= 1.0.7 =

* added Uploading files by drag and drop method

= 1.0.6 =

* fixed bug with creating previews after update 1.0.5

= 1.0.5 =

* Added statistics for viewing and downloading files.


= 1.0.4 =

* Стилизация кнопок под разные темы
* Переименование файла, папки


== Upgrade Notice ==

= 1.00 = 
В базе создаються таблицы: _krdisk_dirs, _krdisk_files, _krdisk_task


