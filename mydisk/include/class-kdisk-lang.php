<?php 

class KDisk_Lang{

	static function createJS( $file_name )
	{		
		$jsbody = 'function KDisk_Lang(txt){
		var lang = {
		"Download" : "' . esc_html(_x('Download','download file',KDISK_PLG)) . '",
    	"Delete" : "' . esc_html(_x('Delete','delete file',KDISK_PLG)) . '",
	    "Copy URL" : "' . esc_html(_x('Copy URL','Copy URL',KDISK_PLG)) . '",
	    "Convert video" : "' . esc_html(_x('Convert video','Convert video',KDISK_PLG)) . '",
    	"Downloading"	: "' . esc_html(_x('Downloading','Downloading',KDISK_PLG)) . '",
		"Convert video?"	: "' . esc_html(_x('Convert video?','Convert video?',KDISK_PLG)) . '",
		"Empty Trash Permanently?" : "' . esc_html(_x('Empty Trash Permanently?','Trash',KDISK_PLG)) . '", 
		"Delete permanently?" : "' . esc_html(_x('Delete permanently?','Trash',KDISK_PLG)) . '", 
		"Restore?" : "' . esc_html(_x('Restore?','Trash',KDISK_PLG)) . '", 
		"Restore" : "' . esc_html(_x('Restore','Trash',KDISK_PLG)) . '", 
		"Delete?" : "' . esc_html(_x('Delete?','Trash',KDISK_PLG)) . '", 
		"New folder name" : "' . esc_html(__('New folder name',KDISK_PLG)) . '", 
		"Name cannot exceed 32 characters" : "' . esc_html(__('Name cannot exceed 32 characters',KDISK_PLG)) . '", 
		"Link copied to clipboard" : "' . esc_html(__('Link copied to clipboard',KDISK_PLG)) . '", 
		"Cancel" : "' . esc_html(__('Cancel',KDISK_PLG)) . '", 
		"Create" : "' . esc_html(__('OK',KDISK_PLG)) . '",
		"Properties" : "' . esc_html(__('Properties',KDISK_PLG)) . '", 
		"Yes" : "' . esc_html(__('Yes',KDISK_PLG)) . '",  
		"No" : "' . esc_html(__('No',KDISK_PLG)) . '",  
		"Take audio track" : "' . esc_html(__('Take audio track',KDISK_PLG)) . '",  
		"Combine" : "' . esc_html(__('Combine video and audio',KDISK_PLG)) . '",  
				
		};
		if (typeof(lang[txt])=="undefined")return txt;
	return lang[txt];
}';

		$fi = fopen( $file_name, "w+" );  
		fwrite($fi, $jsbody);
		fclose($fi);
	}
}