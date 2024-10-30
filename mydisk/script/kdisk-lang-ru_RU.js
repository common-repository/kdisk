function KDisk_Lang(txt){
		var lang = {
		"Download" : "Скачать",
    	"Delete" : "Удалить",
	    "Copy URL" : "Скопировать ссылку",
	    "Convert video" : "Конвертировать видео",
    	"Downloading"	: "Скачивание",
		"Convert video?"	: "Cконвертировать видео?",
		"Empty Trash Permanently?" : "Очистить корзину навсегда?", 
		"Delete permanently?" : "Удалить навсегда?", 
		"Restore?" : "Восстановить?", 
		"Restore" : "Восстановить", 
		"Delete?" : "Удалить?", 
		"New folder name" : "Название новой папки", 
		"Name cannot exceed 32 characters" : "Название не может быть длиннее 32 символов", 
		"Link copied to clipboard" : "Ссылка скопирована в буфер обмена", 
		"Cancel" : "Отмена", 
		"Create" : "Создать",
		"Properties" : "Свойства", 
		"Yes" : "Да",  
		"No" : "Нет",  
		"Take audio track" : "Извлечь звуковую дорожку",  
		"Combine" : "Объединить видео и звук",  
				
		};
		if (typeof(lang[txt])=="undefined")return txt;
	return lang[txt];
}