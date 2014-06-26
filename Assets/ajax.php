<?php

// ================================================================================
// This section contains definitions of database connection for the @DBcall command
// ================================================================================

$DBconnections = array(
	array(
		"id" => "DB1",
		"type" => "SQLITE",
		"filename" => "mysqlitedb.db"
	)
	,
	array(
		"id" => "DB2",
		"type" => "MYSQL",
		"host" => "localhost",
		"schema" => "maindb",
		"user" => "user",
		"pwd" => "password"
	)
);

// ================================================================================

define("UPLOAD_PATH", "upload");
define("UPLOAD_MAXSIZE", 1000000);

// ================================================================================
// ==  Support Functions  (text search for AJAX to jump to ajax functions)
// ================================================================================

function PostToHost($url, $data) {
	$pos = strrpos($url, "://");
	if ($pos === false) { 
		$url = "http://".$url;
	}
	$u = parse_url($url);
	$p = isset($u['path']) ? $u['path'] : '/';
	if (isset($u['query']))
		if ( $u['query'] != '' )
			$p .= '?' . $u['query'] ;
	if (! isset($u['host']))
		$h = "localhost" ;
	else
		$h = $u['host'] ;
	$fp = fsockopen($h,80,$errno,$errstr,30);

	$s = "POST " . $p . " HTTP/1.1\r\n" .
	"Host: " . $h . "\r\n" .
	"Content-Type: application/x-www-form-urlencoded\r\n" .
	"Content-Length: ".strlen($data)."\r\n" .
	"Connection: close\r\n\r\n" . 
	$data ; 

	fputs($fp , $s ) ;

	$buf = NULL ; 
	while(!feof($fp)) { 
		$buf .= fgets($fp,128);
	} 
	fclose($fp);

	// IIS can return multiple headers!!!!
	while ( substr($buf,0,4) == "HTTP" ) {
		$buf = substr($buf, strpos($buf, "\r\n\r\n") + 4);
	}

	return $buf ;
} 

function assocArrayToArrayOfArrayOfString($assoc) {
	$arrayKeys = array_keys($assoc);
	$arrayValues = array_values($assoc);

	return array($arrayKeys, $arrayValues);
}
 
function multiAssocArrayToArrayOfArrayOfString($multi) {
	$arrayKeys = array_keys($multi[0]);
	$arrayValues = array();

	foreach ($multi as $v) {
		$arrayValues[] = array_values($v);
	}

	$_arrayKeys = array();
	$_arrayKeys[0] = $arrayKeys;

	return array_merge($_arrayKeys, $arrayValues);
}

function SureRemoveDir($dir, $DeleteMe)
{
	if(!$dh = @opendir($dir)) return;
	while (false !== ($obj = readdir($dh))) {
		if($obj=='.' || $obj=='..')
			continue;
		if (!@unlink($dir.'/'.$obj))
			SureRemoveDir($dir.'/'.$obj, true);
	}

	closedir($dh);
	if ($DeleteMe)
		@rmdir($dir);
}

class dUnzip2{
	Function getVersion(){
		return "2.6";
	}
	// Public
	var $fileName;
	var $compressedList; // You will problably use only this one!
	var $centralDirList; // Central dir list... It's a kind of 'extra attributes' for a set of files
	var $endOfCentral;   // End of central dir, contains ZIP Comments
	var $debug;
	
	// Private
	var $fh;
	var $zipSignature = "\x50\x4b\x03\x04"; // local file header signature
	var $dirSignature = "\x50\x4b\x01\x02"; // central dir header signature
	var $dirSignatureE= "\x50\x4b\x05\x06"; // end of central dir signature
	
	// Public
	Function dUnzip2($fileName){
		$this->fileName       = $fileName;
		$this->compressedList = 
		$this->centralDirList = 
		$this->endOfCentral   = Array();
	}
	
	Function getList($stopOnFile=false){
		if(sizeof($this->compressedList)){
			$this->debugMsg(1, "Returning already loaded file list.");
			return $this->compressedList;
		}
		
		// Open file, and set file handler
		$fh = fopen($this->fileName, "r");
		$this->fh = &$fh;
		if(!$fh){
			$this->debugMsg(2, "Failed to load file.");
			return false;
		}
		
		$this->debugMsg(1, "Loading list from 'End of Central Dir' index list...");
		if(!$this->_loadFileListByEOF($fh, $stopOnFile)){
			$this->debugMsg(1, "Failed! Trying to load list looking for signatures...");
			if(!$this->_loadFileListBySignatures($fh, $stopOnFile)){
				$this->debugMsg(1, "Failed! Could not find any valid header.");
				$this->debugMsg(2, "ZIP File is corrupted or empty");
				return false;
			}
		}
		return $this->compressedList;
	}
	
	Function getExtraInfo($compressedFileName){
		return
			isset($this->centralDirList[$compressedFileName])?
			$this->centralDirList[$compressedFileName]:
			false;
	}
	
	Function getZipInfo($detail=false){
		return $detail?
			$this->endOfCentral[$detail]:
			$this->endOfCentral;
	}
	
	Function unzip($compressedFileName, $targetFileName=false, $applyChmod=0777){
		if(!sizeof($this->compressedList)){
			$this->debugMsg(1, "Trying to unzip before loading file list... Loading it!");
			$this->getList(false, $compressedFileName);
		}
		
		$fdetails = &$this->compressedList[$compressedFileName];
		if(!isset($this->compressedList[$compressedFileName])){
			$this->debugMsg(2, "File '<b>$compressedFileName</b>' is not compressed in the zip.");
			return false;
		}
		if(substr($compressedFileName, -1) == "/"){
			$this->debugMsg(2, "Trying to unzip a folder name '<b>$compressedFileName</b>'.");
			return false;
		}
		if(!$fdetails['uncompressed_size']){
			$this->debugMsg(1, "File '<b>$compressedFileName</b>' is empty.");
			return $targetFileName?
				file_put_contents($targetFileName, ""):
				"";
		}
		
		fseek($this->fh, $fdetails['contents-startOffset']);
		$ret = $this->uncompress(
				fread($this->fh, $fdetails['compressed_size']),
				$fdetails['compression_method'],
				$fdetails['uncompressed_size'],
				$targetFileName
			);
		if($applyChmod && $targetFileName)
			chmod($targetFileName, 0777);
		
		return $ret;
	}
	Function unzipAll($targetDir=false, $baseDir="", $maintainStructure=true, $applyChmod=0777){
		if($targetDir === false)
			$targetDir = dirname(__FILE__)."/";
		
		$lista = $this->getList();
		if(sizeof($lista)) foreach($lista as $fileName=>$trash){
			$dirname  = dirname($fileName);
			$outDN    = "$targetDir/$dirname";
			
			if(substr($dirname, 0, strlen($baseDir)) != $baseDir)
				continue;
			
			if(!is_dir($outDN) && $maintainStructure){
				$str = "";
				$folders = explode("/", $dirname);
				foreach($folders as $folder){
					$str = $str?"$str/$folder":$folder;
					if(!is_dir("$targetDir/$str")){
						$this->debugMsg(1, "Creating folder: $targetDir/$str");
						mkdir("$targetDir/$str");
						if($applyChmod)
							chmod("$targetDir/$str", $applyChmod);
					}
				}
			}
			if(substr($fileName, -1, 1) == "/")
				continue;
			
			$maintainStructure?
				$this->unzip($fileName, "$targetDir/$fileName", $applyChmod):
				$this->unzip($fileName, "$targetDir/".basename($fileName), $applyChmod);
		}
	}
	
	Function close(){     // Free the file resource
		if($this->fh)
			fclose($this->fh);
	}
	
	Function __destroy(){ 
		$this->close();
	}
	
	// Private (you should NOT call these methods):
	Function uncompress($content, $mode, $uncompressedSize, $targetFileName=false){
		switch($mode){
			case 0:
				// Not compressed
				return $targetFileName?
					file_put_contents($targetFileName, $content):
					$content;
			case 1:
				$this->debugMsg(2, "Shrunk mode is not supported... yet?");
				return false;
			case 2:
			case 3:
			case 4:
			case 5:
				$this->debugMsg(2, "Compression factor ".($mode-1)." is not supported... yet?");
				return false;
			case 6:
				$this->debugMsg(2, "Implode is not supported... yet?");
				return false;
			case 7:
				$this->debugMsg(2, "Tokenizing compression algorithm is not supported... yet?");
				return false;
			case 8:
				// Deflate
				return $targetFileName?
					file_put_contents($targetFileName, gzinflate($content, $uncompressedSize)):
					gzinflate($content, $uncompressedSize);
			case 9:
				$this->debugMsg(2, "Enhanced Deflating is not supported... yet?");
				return false;
			case 10:
				$this->debugMsg(2, "PKWARE Date Compression Library Impoloding is not supported... yet?");
				return false;
           case 12:
               // Bzip2
               return $targetFileName?
                   file_put_contents($targetFileName, bzdecompress($content)):
                   bzdecompress($content);
			case 18:
				$this->debugMsg(2, "IBM TERSE is not supported... yet?");
				return false;
			default:
				$this->debugMsg(2, "Unknown uncompress method: $mode");
				return false;
		}
	}
	
	Function debugMsg($level, $string){
		if($this->debug)
			if($level == 1)
				echo "<b style='color: #777'>dUnzip2:</b> $string<br>";
			if($level == 2)
				echo "<b style='color: #F00'>dUnzip2:</b> $string<br>";
	}

	Function _loadFileListByEOF(&$fh, $stopOnFile=false){
		// Check if there's a valid Central Dir signature.
		// Let's consider a file comment smaller than 1024 characters...
		// Actually, it length can be 65536.. But we're not going to support it.
		
		for($x = 0; $x < 1024; $x++){
			fseek($fh, -22-$x, SEEK_END);
			
			$signature = fread($fh, 4);
			if($signature == $this->dirSignatureE){
				// If found EOF Central Dir
				$eodir['disk_number_this']   = unpack("v", fread($fh, 2)); // number of this disk
				$eodir['disk_number']        = unpack("v", fread($fh, 2)); // number of the disk with the start of the central directory
				$eodir['total_entries_this'] = unpack("v", fread($fh, 2)); // total number of entries in the central dir on this disk
				$eodir['total_entries']      = unpack("v", fread($fh, 2)); // total number of entries in
				$eodir['size_of_cd']         = unpack("V", fread($fh, 4)); // size of the central directory
				$eodir['offset_start_cd']    = unpack("V", fread($fh, 4)); // offset of start of central directory with respect to the starting disk number
				$zipFileCommentLenght        = unpack("v", fread($fh, 2)); // zipfile comment length
				$eodir['zipfile_comment']    = $zipFileCommentLenght[1]?fread($fh, $zipFileCommentLenght[1]):''; // zipfile comment
				$this->endOfCentral = Array(
					'disk_number_this'=>$eodir['disk_number_this'][1],
					'disk_number'=>$eodir['disk_number'][1],
					'total_entries_this'=>$eodir['total_entries_this'][1],
					'total_entries'=>$eodir['total_entries'][1],
					'size_of_cd'=>$eodir['size_of_cd'][1],
					'offset_start_cd'=>$eodir['offset_start_cd'][1],
					'zipfile_comment'=>$eodir['zipfile_comment'],
				);
				
				// Then, load file list
				fseek($fh, $this->endOfCentral['offset_start_cd']);
				$signature = fread($fh, 4);
				
				while($signature == $this->dirSignature){
					$dir['version_madeby']      = unpack("v", fread($fh, 2)); // version made by
					$dir['version_needed']      = unpack("v", fread($fh, 2)); // version needed to extract
					$dir['general_bit_flag']    = unpack("v", fread($fh, 2)); // general purpose bit flag
					$dir['compression_method']  = unpack("v", fread($fh, 2)); // compression method
					$dir['lastmod_time']        = unpack("v", fread($fh, 2)); // last mod file time
					$dir['lastmod_date']        = unpack("v", fread($fh, 2)); // last mod file date
					$dir['crc-32']              = fread($fh, 4);              // crc-32
					$dir['compressed_size']     = unpack("V", fread($fh, 4)); // compressed size
					$dir['uncompressed_size']   = unpack("V", fread($fh, 4)); // uncompressed size
					$fileNameLength             = unpack("v", fread($fh, 2)); // filename length
					$extraFieldLength           = unpack("v", fread($fh, 2)); // extra field length
					$fileCommentLength          = unpack("v", fread($fh, 2)); // file comment length
					$dir['disk_number_start']   = unpack("v", fread($fh, 2)); // disk number start
					$dir['internal_attributes'] = unpack("v", fread($fh, 2)); // internal file attributes-byte1
					$dir['external_attributes1']= unpack("v", fread($fh, 2)); // external file attributes-byte2
					$dir['external_attributes2']= unpack("v", fread($fh, 2)); // external file attributes
					$dir['relative_offset']     = unpack("V", fread($fh, 4)); // relative offset of local header
					$dir['file_name']           = fread($fh, $fileNameLength[1]);                             // filename
					$dir['extra_field']         = $extraFieldLength[1] ?fread($fh, $extraFieldLength[1]) :''; // extra field
					$dir['file_comment']        = $fileCommentLength[1]?fread($fh, $fileCommentLength[1]):''; // file comment			
					
					// Convert the date and time, from MS-DOS format to UNIX Timestamp
					$BINlastmod_date = str_pad(decbin($dir['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
					$BINlastmod_time = str_pad(decbin($dir['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);
					$lastmod_dateY = bindec(substr($BINlastmod_date,  0, 7))+1980;
					$lastmod_dateM = bindec(substr($BINlastmod_date,  7, 4));
					$lastmod_dateD = bindec(substr($BINlastmod_date, 11, 5));
					$lastmod_timeH = bindec(substr($BINlastmod_time,   0, 5));
					$lastmod_timeM = bindec(substr($BINlastmod_time,   5, 6));
					$lastmod_timeS = bindec(substr($BINlastmod_time,  11, 5));	
					
					$this->centralDirList[$dir['file_name']] = Array(
						'version_madeby'=>$dir['version_madeby'][1],
						'version_needed'=>$dir['version_needed'][1],
						'general_bit_flag'=>str_pad(decbin($dir['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
						'compression_method'=>$dir['compression_method'][1],
						'lastmod_datetime'  =>mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY),
						'crc-32'            =>str_pad(dechex(ord($dir['crc-32'][3])), 2, '0', STR_PAD_LEFT).
											  str_pad(dechex(ord($dir['crc-32'][2])), 2, '0', STR_PAD_LEFT).
											  str_pad(dechex(ord($dir['crc-32'][1])), 2, '0', STR_PAD_LEFT).
											  str_pad(dechex(ord($dir['crc-32'][0])), 2, '0', STR_PAD_LEFT),
						'compressed_size'=>$dir['compressed_size'][1],
						'uncompressed_size'=>$dir['uncompressed_size'][1],
						'disk_number_start'=>$dir['disk_number_start'][1],
						'internal_attributes'=>$dir['internal_attributes'][1],
						'external_attributes1'=>$dir['external_attributes1'][1],
						'external_attributes2'=>$dir['external_attributes2'][1],
						'relative_offset'=>$dir['relative_offset'][1],
						'file_name'=>$dir['file_name'],
						'extra_field'=>$dir['extra_field'],
						'file_comment'=>$dir['file_comment'],
					);
					$signature = fread($fh, 4);
				}
				
				// If loaded centralDirs, then try to identify the offsetPosition of the compressed data.
				if($this->centralDirList) foreach($this->centralDirList as $filename=>$details){
					$i = $this->_getFileHeaderInformation($fh, $details['relative_offset']);
					$this->compressedList[$filename]['file_name']          = $filename;
					$this->compressedList[$filename]['compression_method'] = $details['compression_method'];
					$this->compressedList[$filename]['version_needed']     = $details['version_needed'];
					$this->compressedList[$filename]['lastmod_datetime']   = $details['lastmod_datetime'];
					$this->compressedList[$filename]['crc-32']             = $details['crc-32'];
					$this->compressedList[$filename]['compressed_size']    = $details['compressed_size'];
					$this->compressedList[$filename]['uncompressed_size']  = $details['uncompressed_size'];
					$this->compressedList[$filename]['lastmod_datetime']   = $details['lastmod_datetime'];
					$this->compressedList[$filename]['extra_field']        = $i['extra_field'];
					$this->compressedList[$filename]['contents-startOffset']=$i['contents-startOffset'];
					if(strtolower($stopOnFile) == strtolower($filename))
						break;
				}
				return true;
			}
		}
		return false;
	}
	
	Function _loadFileListBySignatures(&$fh, $stopOnFile=false){
		fseek($fh, 0);
		
		$return = false;
		for(;;){
			$details = $this->_getFileHeaderInformation($fh);
			if(!$details){
				$this->debugMsg(1, "Invalid signature. Trying to verify if is old style Data Descriptor...");
				fseek($fh, 12 - 4, SEEK_CUR); // 12: Data descriptor - 4: Signature (that will be read again)
				$details = $this->_getFileHeaderInformation($fh);
			}
			if(!$details){
				$this->debugMsg(1, "Still invalid signature. Probably reached the end of the file.");
				break;
			}
			$filename = $details['file_name'];
			$this->compressedList[$filename] = $details;
			$return = true;
			if(strtolower($stopOnFile) == strtolower($filename))
				break;
		}
		
		return $return;
	}
	
	Function _getFileHeaderInformation(&$fh, $startOffset=false){
		if($startOffset !== false)
			fseek($fh, $startOffset);
		
		$signature = fread($fh, 4);
		if($signature == $this->zipSignature){
			# $this->debugMsg(1, "Zip Signature!");
			
			// Get information about the zipped file
			$file['version_needed']     = unpack("v", fread($fh, 2)); // version needed to extract
			$file['general_bit_flag']   = unpack("v", fread($fh, 2)); // general purpose bit flag
			$file['compression_method'] = unpack("v", fread($fh, 2)); // compression method
			$file['lastmod_time']       = unpack("v", fread($fh, 2)); // last mod file time
			$file['lastmod_date']       = unpack("v", fread($fh, 2)); // last mod file date
			$file['crc-32']             = fread($fh, 4);              // crc-32
			$file['compressed_size']    = unpack("V", fread($fh, 4)); // compressed size
			$file['uncompressed_size']  = unpack("V", fread($fh, 4)); // uncompressed size
			$fileNameLength             = unpack("v", fread($fh, 2)); // filename length
			$extraFieldLength           = unpack("v", fread($fh, 2)); // extra field length
			$file['file_name']          = fread($fh, $fileNameLength[1]); // filename
			$file['extra_field']        = $extraFieldLength[1]?fread($fh, $extraFieldLength[1]):''; // extra field
			$file['contents-startOffset']= ftell($fh);
			
			// Bypass the whole compressed contents, and look for the next file
			fseek($fh, $file['compressed_size'][1], SEEK_CUR);
			
			// Convert the date and time, from MS-DOS format to UNIX Timestamp
			$BINlastmod_date = str_pad(decbin($file['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
			$BINlastmod_time = str_pad(decbin($file['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);
			$lastmod_dateY = bindec(substr($BINlastmod_date,  0, 7))+1980;
			$lastmod_dateM = bindec(substr($BINlastmod_date,  7, 4));
			$lastmod_dateD = bindec(substr($BINlastmod_date, 11, 5));
			$lastmod_timeH = bindec(substr($BINlastmod_time,   0, 5));
			$lastmod_timeM = bindec(substr($BINlastmod_time,   5, 6));
			$lastmod_timeS = bindec(substr($BINlastmod_time,  11, 5));
			
			// Mount file table
			$i = Array(
				'file_name'         =>$file['file_name'],
				'compression_method'=>$file['compression_method'][1],
				'version_needed'    =>$file['version_needed'][1],
				'lastmod_datetime'  =>mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY),
				'crc-32'            =>str_pad(dechex(ord($file['crc-32'][3])), 2, '0', STR_PAD_LEFT).
									  str_pad(dechex(ord($file['crc-32'][2])), 2, '0', STR_PAD_LEFT).
									  str_pad(dechex(ord($file['crc-32'][1])), 2, '0', STR_PAD_LEFT).
									  str_pad(dechex(ord($file['crc-32'][0])), 2, '0', STR_PAD_LEFT),
				'compressed_size'   =>$file['compressed_size'][1],
				'uncompressed_size' =>$file['uncompressed_size'][1],
				'extra_field'       =>$file['extra_field'],
				'general_bit_flag'  =>str_pad(decbin($file['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
				'contents-startOffset'=>$file['contents-startOffset']
			);
			return $i;
		}
		return false;
	}
}


/*--------------------------------------------------
 | TAR/GZIP/BZIP2/ZIP ARCHIVE CLASSES 2.1
 | By Devin Doucette
 | Copyright (c) 2005 Devin Doucette
 | Email: darksnoopy@shaw.ca
 +--------------------------------------------------
 | Email bugs/suggestions to darksnoopy@shaw.ca
 +--------------------------------------------------
 | This script has been created and released under
 | the GNU GPL and is free to use and redistribute
 | only if this copyright statement is not removed
 +--------------------------------------------------*/

class archive
{
	function archive($name)
	{
		$this->options = array (
			'basedir' => ".",
			'name' => $name,
			'prepend' => "",
			'inmemory' => 0,
			'overwrite' => 0,
			'recurse' => 1,
			'storepaths' => 1,
			'followlinks' => 0,
			'level' => 3,
			'method' => 1,
			'sfx' => "",
			'type' => "",
			'comment' => ""
		);
		$this->files = array ();
		$this->exclude = array ();
		$this->storeonly = array ();
		$this->error = array ();
	}

	function set_options($options)
	{
		foreach ($options as $key => $value)
			$this->options[$key] = $value;
		if (!empty ($this->options['basedir']))
		{
			$this->options['basedir'] = str_replace("\\", "/", $this->options['basedir']);
			$this->options['basedir'] = preg_replace("/\/+/", "/", $this->options['basedir']);
			$this->options['basedir'] = preg_replace("/\/$/", "", $this->options['basedir']);
		}
		if (!empty ($this->options['name']))
		{
			$this->options['name'] = str_replace("\\", "/", $this->options['name']);
			$this->options['name'] = preg_replace("/\/+/", "/", $this->options['name']);
		}
		if (!empty ($this->options['prepend']))
		{
			$this->options['prepend'] = str_replace("\\", "/", $this->options['prepend']);
			$this->options['prepend'] = preg_replace("/^(\.*\/+)+/", "", $this->options['prepend']);
			$this->options['prepend'] = preg_replace("/\/+/", "/", $this->options['prepend']);
			$this->options['prepend'] = preg_replace("/\/$/", "", $this->options['prepend']) . "/";
		}
	}

	function create_archive()
	{
		$this->make_list();

		if ($this->options['inmemory'] == 0)
		{
			$pwd = getcwd();
			chdir($this->options['basedir']);
			if ($this->options['overwrite'] == 0 && file_exists($this->options['name'] . ($this->options['type'] == "gzip" || $this->options['type'] == "bzip" ? ".tmp" : "")))
			{
				$this->error[] = "File {$this->options['name']} already exists.";
				chdir($pwd);
				return 0;
			}
			else if ($this->archive = @fopen($this->options['name'] . ($this->options['type'] == "gzip" || $this->options['type'] == "bzip" ? ".tmp" : ""), "wb+"))
				chdir($pwd);
			else
			{
				$this->error[] = "Could not open {$this->options['name']} for writing.";
				chdir($pwd);
				return 0;
			}
		}
		else
			$this->archive = "";

		switch ($this->options['type'])
		{
		case "zip":
			if (!$this->create_zip())
			{
				$this->error[] = "Could not create zip file.";
				return 0;
			}
			break;
		case "bzip":
			if (!$this->create_tar())
			{
				$this->error[] = "Could not create tar file.";
				return 0;
			}
			if (!$this->create_bzip())
			{
				$this->error[] = "Could not create bzip2 file.";
				return 0;
			}
			break;
		case "gzip":
			if (!$this->create_tar())
			{
				$this->error[] = "Could not create tar file.";
				return 0;
			}
			if (!$this->create_gzip())
			{
				$this->error[] = "Could not create gzip file.";
				return 0;
			}
			break;
		case "tar":
			if (!$this->create_tar())
			{
				$this->error[] = "Could not create tar file.";
				return 0;
			}
		}

		if ($this->options['inmemory'] == 0)
		{
			fclose($this->archive);
			if ($this->options['type'] == "gzip" || $this->options['type'] == "bzip")
				unlink($this->options['basedir'] . "/" . $this->options['name'] . ".tmp");
		}
	}

	function add_data($data)
	{
		if ($this->options['inmemory'] == 0)
			fwrite($this->archive, $data);
		else
			$this->archive .= $data;
	}

	function make_list()
	{
		if (!empty ($this->exclude))
			foreach ($this->files as $key => $value)
				foreach ($this->exclude as $current)
					if ($value['name'] == $current['name'])
						unset ($this->files[$key]);
		if (!empty ($this->storeonly))
			foreach ($this->files as $key => $value)
				foreach ($this->storeonly as $current)
					if ($value['name'] == $current['name'])
						$this->files[$key]['method'] = 0;
		unset ($this->exclude, $this->storeonly);
	}

	function add_files($list)
	{
		$temp = $this->list_files($list);
		foreach ($temp as $current)
			$this->files[] = $current;
	}

	function exclude_files($list)
	{
		$temp = $this->list_files($list);
		foreach ($temp as $current)
			$this->exclude[] = $current;
	}

	function store_files($list)
	{
		$temp = $this->list_files($list);
		foreach ($temp as $current)
			$this->storeonly[] = $current;
	}

	function list_files($list)
	{
		if (!is_array ($list))
		{
			$temp = $list;
			$list = array ($temp);
			unset ($temp);
		}

		$files = array ();

		$pwd = getcwd();
		chdir($this->options['basedir']);

		foreach ($list as $current)
		{
			$current = str_replace("\\", "/", $current);
			$current = preg_replace("/\/+/", "/", $current);
			$current = preg_replace("/\/$/", "", $current);
			if (strstr($current, "*"))
			{
				$regex = preg_replace("/([\\\^\$\.\[\]\|\(\)\?\+\{\}\/])/", "\\\\\\1", $current);
				$regex = str_replace("*", ".*", $regex);
				$dir = strstr($current, "/") ? substr($current, 0, strrpos($current, "/")) : ".";
				$temp = $this->parse_dir($dir);
				foreach ($temp as $current2)
					if (preg_match("/^{$regex}$/i", $current2['name']))
						$files[] = $current2;
				unset ($regex, $dir, $temp, $current);
			}
			else if (@is_dir($current))
			{
				$temp = $this->parse_dir($current);
				foreach ($temp as $file)
					$files[] = $file;
				unset ($temp, $file);
			}
			else if (@file_exists($current))
				$files[] = array ('name' => $current, 'name2' => $this->options['prepend'] .
					preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($current, "/")) ?
					substr($current, strrpos($current, "/") + 1) : $current),
					'type' => @is_link($current) && $this->options['followlinks'] == 0 ? 2 : 0,
					'ext' => substr($current, strrpos($current, ".")), 'stat' => stat($current));
		}

		chdir($pwd);

		unset ($current, $pwd);

		usort($files, array ("archive", "sort_files"));

		return $files;
	}

	function parse_dir($dirname)
	{
		if ($this->options['storepaths'] == 1 && !preg_match("/^(\.+\/*)+$/", $dirname))
			$files = array (array ('name' => $dirname, 'name2' => $this->options['prepend'] .
				preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($dirname, "/")) ?
				substr($dirname, strrpos($dirname, "/") + 1) : $dirname), 'type' => 5, 'stat' => stat($dirname)));
		else
			$files = array ();
		$dir = @opendir($dirname);

		while ($file = @readdir($dir))
		{
			$fullname = $dirname . "/" . $file;
			if ($file == "." || $file == "..")
				continue;
			else if (@is_dir($fullname))
			{
				if (empty ($this->options['recurse']))
					continue;
				$temp = $this->parse_dir($fullname);
				foreach ($temp as $file2)
					$files[] = $file2;
			}
			else if (@file_exists($fullname))
				$files[] = array ('name' => $fullname, 'name2' => $this->options['prepend'] .
					preg_replace("/(\.+\/+)+/", "", ($this->options['storepaths'] == 0 && strstr($fullname, "/")) ?
					substr($fullname, strrpos($fullname, "/") + 1) : $fullname),
					'type' => @is_link($fullname) && $this->options['followlinks'] == 0 ? 2 : 0,
					'ext' => substr($file, strrpos($file, ".")), 'stat' => stat($fullname));
		}

		@closedir($dir);

		return $files;
	}

	function sort_files($a, $b)
	{
		if ($a['type'] != $b['type'])
			if ($a['type'] == 5 || $b['type'] == 2)
				return -1;
			else if ($a['type'] == 2 || $b['type'] == 5)
				return 1;
		else if ($a['type'] == 5)
			return strcmp(strtolower($a['name']), strtolower($b['name']));
		else if ($a['ext'] != $b['ext'])
			return strcmp($a['ext'], $b['ext']);
		else if ($a['stat'][7] != $b['stat'][7])
			return $a['stat'][7] > $b['stat'][7] ? -1 : 1;
		else
			return strcmp(strtolower($a['name']), strtolower($b['name']));
		return 0;
	}

	function download_file()
	{
		if ($this->options['inmemory'] == 0)
		{
			$this->error[] = "Can only use download_file() if archive is in memory. Redirect to file otherwise, it is faster.";
			return;
		}
		switch ($this->options['type'])
		{
		case "zip":
			header("Content-Type: application/zip");
			break;
		case "bzip":
			header("Content-Type: application/x-bzip2");
			break;
		case "gzip":
			header("Content-Type: application/x-gzip");
			break;
		case "tar":
			header("Content-Type: application/x-tar");
		}
		$header = "Content-Disposition: attachment; filename=\"";
		$header .= strstr($this->options['name'], "/") ? substr($this->options['name'], strrpos($this->options['name'], "/") + 1) : $this->options['name'];
		$header .= "\"";
		header($header);
		header("Content-Length: " . strlen($this->archive));
		header("Content-Transfer-Encoding: binary");
		header("Cache-Control: no-cache, must-revalidate, max-age=60");
		header("Expires: Sat, 01 Jan 2000 12:00:00 GMT");
		print($this->archive);
	}
}

class tar_file extends archive
{
	function tar_file($name)
	{
		$this->archive($name);
		$this->options['type'] = "tar";
	}

	function create_tar()
	{
		$pwd = getcwd();
		chdir($this->options['basedir']);

		foreach ($this->files as $current)
		{
			if ($current['name'] == $this->options['name'])
				continue;
			if (strlen($current['name2']) > 99)
			{
				$path = substr($current['name2'], 0, strpos($current['name2'], "/", strlen($current['name2']) - 100) + 1);
				$current['name2'] = substr($current['name2'], strlen($path));
				if (strlen($path) > 154 || strlen($current['name2']) > 99)
				{
					$this->error[] = "Could not add {$path}{$current['name2']} to archive because the filename is too long.";
					continue;
				}
			}
			$block = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12", $current['name2'], sprintf("%07o", 
				$current['stat'][2]), sprintf("%07o", $current['stat'][4]), sprintf("%07o", $current['stat'][5]), 
				sprintf("%011o", $current['type'] == 2 ? 0 : $current['stat'][7]), sprintf("%011o", $current['stat'][9]), 
				"        ", $current['type'], $current['type'] == 2 ? @readlink($current['name']) : "", "ustar ", " ", 
				"Unknown", "Unknown", "", "", !empty ($path) ? $path : "", "");

			$checksum = 0;
			for ($i = 0; $i < 512; $i++)
				$checksum += ord(substr($block, $i, 1));
			$checksum = pack("a8", sprintf("%07o", $checksum));
			$block = substr_replace($block, $checksum, 148, 8);

			if ($current['type'] == 2 || $current['stat'][7] == 0)
				$this->add_data($block);
			else if ($fp = @fopen($current['name'], "rb"))
			{
				$this->add_data($block);
				while ($temp = fread($fp, 1048576))
					$this->add_data($temp);
				if ($current['stat'][7] % 512 > 0)
				{
					$temp = "";
					for ($i = 0; $i < 512 - $current['stat'][7] % 512; $i++)
						$temp .= "\0";
					$this->add_data($temp);
				}
				fclose($fp);
			}
			else
				$this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
		}

		$this->add_data(pack("a1024", ""));

		chdir($pwd);

		return 1;
	}

	function extract_files()
	{
		$pwd = getcwd();
		chdir($this->options['basedir']);

		if ($fp = $this->open_archive())
		{
			if ($this->options['inmemory'] == 1)
				$this->files = array ();

			while ($block = fread($fp, 512))
			{
				$temp = unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp", $block);
				$file = array (
					'name' => $temp['prefix'] . $temp['name'],
					'stat' => array (
						2 => $temp['mode'],
						4 => octdec($temp['uid']),
						5 => octdec($temp['gid']),
						7 => octdec($temp['size']),
						9 => octdec($temp['mtime']),
					),
					'checksum' => octdec($temp['checksum']),
					'type' => $temp['type'],
					'magic' => $temp['magic'],
				);
				if ($file['checksum'] == 0x00000000)
					break;
				else if (substr($file['magic'], 0, 5) != "ustar")
				{
					$this->error[] = "This script does not support extracting this type of tar file.";
					break;
				}
				$block = substr_replace($block, "        ", 148, 8);
				$checksum = 0;
				for ($i = 0; $i < 512; $i++)
					$checksum += ord(substr($block, $i, 1));
				if ($file['checksum'] != $checksum)
					$this->error[] = "Could not extract from {$this->options['name']}, it is corrupt.";

				if ($this->options['inmemory'] == 1)
				{
					$file['data'] = fread($fp, $file['stat'][7]);
					fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
					unset ($file['checksum'], $file['magic']);
					$this->files[] = $file;
				}
				else if ($file['type'] == 5)
				{
					if (!is_dir($file['name']))
						mkdir($file['name'], $file['stat'][2]);
				}
				else if ($this->options['overwrite'] == 0 && file_exists($file['name']))
				{
					$this->error[] = "{$file['name']} already exists.";
					continue;
				}
				else if ($file['type'] == 2)
				{
					symlink($temp['symlink'], $file['name']);
					chmod($file['name'], $file['stat'][2]);
				}
				else if ($new = @fopen($file['name'], "wb"))
				{
					fwrite($new, fread($fp, $file['stat'][7]));
					fread($fp, (512 - $file['stat'][7] % 512) == 512 ? 0 : (512 - $file['stat'][7] % 512));
					fclose($new);
					chmod($file['name'], $file['stat'][2]);
				}
				else
				{
					$this->error[] = "Could not open {$file['name']} for writing.";
					continue;
				}
				chown($file['name'], $file['stat'][4]);
				chgrp($file['name'], $file['stat'][5]);
				touch($file['name'], $file['stat'][9]);
				unset ($file);
			}
		}
		else
			$this->error[] = "Could not open file {$this->options['name']}";

		chdir($pwd);
	}

	function open_archive()
	{
		return @fopen($this->options['name'], "rb");
	}
}

class gzip_file extends tar_file
{
	function gzip_file($name)
	{
		$this->tar_file($name);
		$this->options['type'] = "gzip";
	}

	function create_gzip()
	{
		if ($this->options['inmemory'] == 0)
		{
			$pwd = getcwd();
			chdir($this->options['basedir']);
			if ($fp = gzopen($this->options['name'], "wb{$this->options['level']}"))
			{
				fseek($this->archive, 0);
				while ($temp = fread($this->archive, 1048576))
					gzwrite($fp, $temp);
				gzclose($fp);
				chdir($pwd);
			}
			else
			{
				$this->error[] = "Could not open {$this->options['name']} for writing.";
				chdir($pwd);
				return 0;
			}
		}
		else
			$this->archive = gzencode($this->archive, $this->options['level']);

		return 1;
	}

	function open_archive()
	{
		return @gzopen($this->options['name'], "rb");
	}
}

class bzip_file extends tar_file
{
	function bzip_file($name)
	{
		$this->tar_file($name);
		$this->options['type'] = "bzip";
	}

	function create_bzip()
	{
		if ($this->options['inmemory'] == 0)
		{
			$pwd = getcwd();
			chdir($this->options['basedir']);
			if ($fp = bzopen($this->options['name'], "wb"))
			{
				fseek($this->archive, 0);
				while ($temp = fread($this->archive, 1048576))
					bzwrite($fp, $temp);
				bzclose($fp);
				chdir($pwd);
			}
			else
			{
				$this->error[] = "Could not open {$this->options['name']} for writing.";
				chdir($pwd);
				return 0;
			}
		}
		else
			$this->archive = bzcompress($this->archive, $this->options['level']);

		return 1;
	}

	function open_archive()
	{
		return @bzopen($this->options['name'], "rb");
	}
}

class zip_file extends archive
{
	function zip_file($name)
	{
		$this->archive($name);
		$this->options['type'] = "zip";
	}

	function create_zip()
	{
		$files = 0;
		$offset = 0;
		$central = "";

		if (!empty ($this->options['sfx']))
			if ($fp = @fopen($this->options['sfx'], "rb"))
			{
				$temp = fread($fp, filesize($this->options['sfx']));
				fclose($fp);
				$this->add_data($temp);
				$offset += strlen($temp);
				unset ($temp);
			}
			else
				$this->error[] = "Could not open sfx module from {$this->options['sfx']}.";

		$pwd = getcwd();
		chdir($this->options['basedir']);

		foreach ($this->files as $current)
		{
			if ($current['name'] == $this->options['name'])
				continue;

			$timedate = explode(" ", date("Y n j G i s", $current['stat'][9]));
			$timedate = ($timedate[0] - 1980 << 25) | ($timedate[1] << 21) | ($timedate[2] << 16) |
				($timedate[3] << 11) | ($timedate[4] << 5) | ($timedate[5]);

			$block = pack("VvvvV", 0x04034b50, 0x000A, 0x0000, (isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate);

			if ($current['stat'][7] == 0 && $current['type'] == 5)
			{
				$block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']) + 1, 0x0000);
				$block .= $current['name2'] . "/";
				$this->add_data($block);
				$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000,
					(isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate,
					0x00000000, 0x00000000, 0x00000000, strlen($current['name2']) + 1, 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
				$central .= $current['name2'] . "/";
				$files++;
				$offset += (31 + strlen($current['name2']));
			}
			else if ($current['stat'][7] == 0)
			{
				$block .= pack("VVVvv", 0x00000000, 0x00000000, 0x00000000, strlen($current['name2']), 0x0000);
				$block .= $current['name2'];
				$this->add_data($block);
				$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000,
					(isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate,
					0x00000000, 0x00000000, 0x00000000, strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, $current['type'] == 5 ? 0x00000010 : 0x00000000, $offset);
				$central .= $current['name2'];
				$files++;
				$offset += (30 + strlen($current['name2']));
			}
			else if ($fp = @fopen($current['name'], "rb"))
			{
				$temp = fread($fp, $current['stat'][7]);
				fclose($fp);
				$crc32 = crc32($temp);
				if (!isset($current['method']) && $this->options['method'] == 1)
				{
					$temp = gzcompress($temp, $this->options['level']);
					$size = strlen($temp) - 6;
					$temp = substr($temp, 2, $size);
				}
				else
					$size = strlen($temp);
				$block .= pack("VVVvv", $crc32, $size, $current['stat'][7], strlen($current['name2']), 0x0000);
				$block .= $current['name2'];
				$this->add_data($block);
				$this->add_data($temp);
				unset ($temp);
				$central .= pack("VvvvvVVVVvvvvvVV", 0x02014b50, 0x0014, $this->options['method'] == 0 ? 0x0000 : 0x000A, 0x0000,
					(isset($current['method']) || $this->options['method'] == 0) ? 0x0000 : 0x0008, $timedate,
					$crc32, $size, $current['stat'][7], strlen($current['name2']), 0x0000, 0x0000, 0x0000, 0x0000, 0x00000000, $offset);
				$central .= $current['name2'];
				$files++;
				$offset += (30 + strlen($current['name2']) + $size);
			}
			else
				$this->error[] = "Could not open file {$current['name']} for reading. It was not added.";
		}

		$this->add_data($central);

		$this->add_data(pack("VvvvvVVv", 0x06054b50, 0x0000, 0x0000, $files, $files, strlen($central), $offset,
			!empty ($this->options['comment']) ? strlen($this->options['comment']) : 0x0000));

		if (!empty ($this->options['comment']))
			$this->add_data($this->options['comment']);

		chdir($pwd);

		return 1;
	}
}

// ================================================================================
// =======  AJAX Function Start Here
// ================================================================================

// Grab the posted params...
if ( isset ( $_POST['method'] ) )
	$method = strtoupper($_POST['method']) ;
else
	$method = strtoupper($_GET['method']) ;

// Return Variable
$retdata = '' ;

// Check the method that's being called
if ( $method == 'DB' )
{
	// Grab the posted params...
	$databaseIdent = $_POST['dbid'] ;
	$sql = $_POST['sql'] ;
	$wantresults = $_POST['retarr'] ;
	$pauseID = $_POST['pauseid'] ;

	$retdata = array('ok'=>TRUE);
	// lookup DB connection
	$db = FALSE;
	foreach ($DBconnections as $DBconn)
		if ($DBconn["id"] == $databaseIdent) {
			$db = $DBconn;
			break;
		}
	if (!$db){
		$retdata['ok'] = FALSE;
		$retdata['error'] = 'Unknown database identifier : ' . $databaseIdent;
	} else {
		// connect to DB
		if ($db["type"] == "SQLITE"){
			$conn = @new SQLite3($db["filename"]);
			if (!$conn)
				$retdata['error'] = 'Could not connect to database : ' . $conn->lastErrorMsg();
			else {
				if ($wantresults){
					$r = @$conn->query($sql);
					if (!$r){
						$retdata['ok'] = FALSE;
						$retdata['error'] = $conn->lastErrorMsg();
					} else {
						$retdata['results'] = array();
						while ($row = $r->fetchArray(SQLITE3_NUM))
							$retdata['results'][] = $row;
					}
				} else {
					if (!@$conn->exec($sql)){
						$retdata['ok'] = FALSE;
						$retdata['error'] = $conn->lastErrorMsg();
					}
				}
				$conn->close();
			}
		} else if ($db["type"] == "MYSQL"){
			$conn = @new mysqli($db["host"], $db["user"], $db["pwd"], $db["schema"]);
			if ($conn->connect_error)
				$retdata['error'] = 'Could not connect to database : ' . $conn->connect_error;
			else {
				$r = @$conn->query($sql);
				if ($r === FALSE){
					$retdata['ok'] = FALSE;
					$retdata['error'] = $conn->error;
				} else if ($r !== TRUE){
					$retdata['results'] = array();
					while ($row = $r->fetch_row())
					  $retdata['results'][] = $row;
					$r->close();
				}
				$conn->close();
			}
		} else {
			$retdata['ok'] = FALSE;
			$retdata['error'] = 'unsupported database type : ' . $db['type'];
		}
	}
	$retdata = 'replayStack.Add(' . $pauseID . ',REPLAY_OBJRETURN,0,0,' . json_encode($retdata) . ')' ;
}
else if ( $method == 'URLFETCH' )
{
	// Grab the posted params...
	$url = $_POST['url'] ;
	$varName = $_POST['varname'] ;
	$varSuccess = ( isset( $_POST['varok'] ) ? $_POST['varok'] : '' ) ;
	$data = ( isset( $_POST['data'] ) ? $_POST['data'] : '' ) ;

	if ( strlen($data) > 0 )
		$contents = PostToHost($url,$data);
	else // assume GET if otherwise
		$contents = file_get_contents($url);

	if ( ! $contents ) {
		$contents = "" ;
		$isOK = 'false' ;
	} else {
		$isOK = 'true' ;
		$contents = addslashes ( $contents ) ;
		$contents = str_replace("\x0d", "\\\\n", $contents);
		$contents = str_replace("\x0a", "", $contents);
		$contents = str_replace("'", "\\\\'", $contents);
		if ( strlen( $contents ) > 5 )
			if ( substr($contents,0,6) == "ERROR:" )
				$isOK = 'false' ;
	}  
	$retdata .= "Cmd_URLfetch_back('" . $varName . "','" . $contents ."','" . $varSuccess . "'," . $isOK .");" ;
}
else if ( $method == 'XMLFETCH' )
{
	// Grab the posted params...
	$varSuccess = $_POST['varok'] ;
	$url = $_POST['url'] ;
	try
	{
		$isOK = '1' ;
		$xml = new SimpleXMLElement($url, NULL, TRUE);
		foreach ( $xml->item as $item)
		{
			$contents = addslashes ( $item['value'] ) ;
			$contents = str_replace("\x0d", "\\\\n", $contents);
			$contents = str_replace("\x0a", "", $contents);
			$retdata .= "Cmd_XMLfetch_back('" . $item['name'] . "','" . $contents ."');" ; 
		} 
	}
	catch(Exception $e)
	{
		$isOK = '0' ;
	}

	$retdata .= "Cmd_XMLfetch_back('" . $varSuccess . "'," . $isOK .");" ;
}
else if ( $method == 'XMLDB' )
{
	// Grab the posted params...
	$file = $_POST['file'] ;
	$varSuccess = $_POST['varok'] ;  
	$varAssign = $_POST['assign'] ;
	$query = $_POST['query'] ;
	$isOK = '0' ;
	try
	{
		$xml = simplexml_load_file ( '../' . $file ) ;
		$res = $xml->xpath($query);  
		if ( count( $res ) > 1 )
			$retdata = 'Multiple query results' ;
		else if ( count( $res ) == 0 )
			$retdata = 'No matching result' ;
		else if ( ! isset ( $res[0][0] ) )
			$retdata = 'Match does not return tag data' ;
		else
		{
			$contents = addslashes ( trim ( $res[0][0] ) ) ;
			$contents = str_replace("\x0d", "\\\\n", $contents);
			$contents = str_replace("\x0a", "", $contents);
			$retdata .= "Cmd_XMLfetch_back('" . $varAssign . "','" . $contents ."');" ; 
			$isOK = '1' ;
		}
	}
	catch(Exception $e)
	{
	}
	$retdata .= "Cmd_XMLfetch_back('" . $varSuccess . "'," . $isOK .");" ;
}
else if ( $method == 'SENDMAIL' )
{
	// Grab the posted params...
	$addr = $_POST['addr'] ;
	$subj = $_POST['subj'] ;
	$body = $_POST['body'] ;

	if ( ! mail( $addr, $subj, $body, "From: web_inference@xpertrule.com" ) )
		$retdata .= "Cmd_SendMail_back(false,'Cannot send!');" ;
	else
		$retdata .= "Cmd_SendMail_back(true,'');" ;
}
else if ( $method == 'DOCVARS' )
{
	// Grab the posted params...
	$filename = strtolower($_POST['fname']) ; 

	$isDOCX = false ;
	$p = strrchr($filename,'.') ;
	if ( $p !== false )
		if ( strtolower($p) == '.docx' )
			$isDOCX = true ;

	if ( $isDOCX ) {
		// unzip the docx file to a temp folder
		$tmpFolder = tempnam(sys_get_temp_dir(), 'XRD');
		unlink ( $tmpFolder ) ;
		mkdir ( $tmpFolder ) ;
		$zip = new dUnzip2('../'.$filename);
		$zip->debug = false;
		$zip->unzipAll( $tmpFolder );
		$zip->close();
		// Read the document
		$fh = fopen($tmpFolder.'/word/document.xml', 'r');
		$body = fread($fh, filesize($tmpFolder.'/word/document.xml'));
		fclose($fh);

		SureRemoveDir ( $tmpFolder , true ) ;

		// Find all the <XR:blah> tags (html special chars in docx)
		preg_match_all('/&lt;(XR|xr):.+?&gt;/',$body,$atts);
		$attributes = array();
		if ( $atts )
			foreach ( $atts[0] as $att ) {
				$name = substr($att,7,strlen($att)-11) ;
				if ( ! in_array($name,$attributes) )
					$attributes[] = $name;
			}
	} else {
		// Read the document
		$fh = fopen('../'.$filename, 'r');
		$body = fread($fh, filesize('../'.$filename));
		fclose($fh);

		// Find all the <XR:blah> tags
		preg_match_all('/<(XR|xr):.+?>/',$body,$atts);
		$attributes = array();
		if ( $atts )
			foreach ( $atts[0] as $att ) {
				$name = substr($att,4,strlen($att)-5) ;
				if ( ! in_array($name,$attributes) )
					$attributes[] = $name;
			}
	}

	// $attributes is an array of all the attribute names in the document
	$s = '' ;
	foreach ( $attributes as $att ) {
		if ( strlen( $s ) > 0 )
			$s .= ',' ;
		$s .= '"' . $att . '"' ;
	}
	// Return a JSON array of the variable names
	$retdata .= "Cmd_XRdoc_vars_back('[" . $s . "]');" ;
} 
else if ( $method == 'DOC' )
{
	// Grab the posted params...
	$retURL   = $_POST['retURL'] ;
	$filename = strtolower($_POST['fname']) ; 

	$isDOCX = false ;
	$p = strrchr($filename,'.') ;
	if ( $p !== false )
		if ( strtolower($p) == '.docx' )
			$isDOCX = true ;

	if ( $isDOCX ) {
		// unzip the docx file to a temp folder
		$tmpFolder = tempnam(sys_get_temp_dir(), 'XRD');
		unlink ( $tmpFolder ) ;
		mkdir ( $tmpFolder ) ;

		$zip = new dUnzip2('../'.$filename);
		$zip->debug = false;
		$zip->unzipAll( $tmpFolder );
		$zip->close();
	
		// Read the document
		$fh = fopen($tmpFolder.'/word/document.xml', 'r');
		$body = fread($fh, filesize($tmpFolder.'/word/document.xml'));
		fclose($fh);
	
		// Call the replace for each parameter passed in the URL post
		foreach ( $_POST as $k => $v )
			if ( ( $k != 'fname' ) && ( $k != 'retURL' ) && ( $k != 'errParam' ) )
				$body = preg_replace ( '/&lt;(XR|xr):' . $k . '&gt;/' , $v , $body ) ;      

		// same the merged file
		$fh = fopen($tmpFolder.'/word/document.xml', 'w');
		fwrite($fh, $body); 
		fclose($fh);

		// re-zip to unique file for download
		$filename = md5 ( rand() * time() ) . '.docx' ;
		$targetdir = dirname(__FILE__) . '/../doc';

		if(!is_dir($targetdir))
			mkdir($targetdir);

		// zip $tmpFolder to $targetdir/$filename
		$ziper = new zip_file($targetdir."/".$filename);
		$ziper->set_options(array('basedir' => $tmpFolder, 'overwrite' => 1, 'recurse' => 1, 'storepaths' => 1)); 
		$ziper->add_files("*.*");
		$ziper->create_archive();

		SureRemoveDir ( $tmpFolder , true ) ;
	} else {
		// Read the document
		$fh = fopen('../'.$filename, 'r');
		$body = fread($fh, filesize('../'.$filename));
		fclose($fh);

		// Call the replace for each parameter passed in the URL post
		foreach ( $_POST as $k => $v )
			if ( ( $k != 'fname' ) && ( $k != 'retURL' ) && ( $k != 'errParam' ) )
				$body = preg_replace ( '/<(XR|xr):' . $k . '>/' , $v , $body ) ;      

		// Save the modified document to a unique file
		$filename = md5 ( rand() * time() ) . '.rtf' ;
		$targetdir = dirname(__FILE__) . '/../doc';
	
		if(!is_dir($targetdir))
			mkdir($targetdir);
		$filename2 = $targetdir . '/' . $filename ;
		$fh = fopen($filename2, 'w');
		fwrite($fh, $body); 
		fclose($fh);
	}

	// Return a URL to the file
	$s = $_SERVER['REQUEST_URI'] ;
	$p = strrpos($s,'/') ;
	if ( $p ) {
		$s = substr($s,0,$p) ;
		$p = strrpos($s,'/') ;
		if ( $p )
			$s = substr($s,0,$p) ;
	}
	  
	if ( isset( $_POST['errParam'] ) )
		$errParams = $_POST['errParam'] ;
	else
		$errParams = '' ;
		
	if ( $errParams == '' ) 
		$ss = '0,' . 'http://' . $_SERVER['HTTP_HOST'] . $s . '/doc/' . $filename ;
	else {
		$errArray = split(",",$errParams) ;
		$ss = count ($errArray) . ',' . 'http://' . $_SERVER['HTTP_HOST'] . $s . '/doc/' . $filename ;
		foreach ($errArray as $tmp) 
			$ss .= ',' . $tmp ;
	}
	$retdata .= "Cmd_XRdoc_back('" . $retURL . "','" . $ss . "');" ;
}
else if ( $method == 'DOCPDF' )
{
	// Grab the posted params...
	$retURL   = $_POST['retURL'] ;
	$filename = strtolower($_POST['fname']) ; 
	$fieldValues = json_decode($_POST['vars'], true) ;
	$uid = $_POST['uid'] ;
	$pwd = $_POST['pwd'] ;

	$baseFolder = '' ;

	// Turn off WSDL caching
	ini_set ('soap.wsdl_cache_enabled', 0);

	// Define credentials for LD
	define ('USERNAME', $uid);
	define ('PASSWORD', $pwd);
	 
	// SOAP WSDL endpoint
	define ('ENDPOINT', 'https://api.livedocx.com/1.2/mailmerge.asmx?WSDL');
	 
	// Define timezone
	date_default_timezone_set('Europe/London');

	if (!file_exists($baseFolder.$filename))
		$retdata .= 'Cmd_Ajax_Err("Cannot find source file : '.$baseFolder.$filename.'")' ;
	else {
		// Instantiate SOAP object and log into LiveDocx

		$soap = new SoapClient(ENDPOINT);

		$soap->LogIn(
			array(
				'username' => USERNAME,
				'password' => PASSWORD
			)
		);

		// Upload template

		$data = file_get_contents($baseFolder.$filename);
		
		$ext = strrchr($filename,'.') ;
		if ( $ext === false )
			$ext = '' ;

		  $soap->SetLocalTemplate(
			  array(
				  'template' => base64_encode($data),
				  'format'   => strtolower($ext)
			  )
		  );

		$soap->SetFieldValues(
			array (
				'fieldValues' => assocArrayToArrayOfArrayOfString($fieldValues)
			)
		);

		// Build the document

		$soap->CreateDocument();

		// Get document as PDF

		$result = $soap->RetrieveDocument(
			array(
				'format' => 'pdf'
			)
		);

		$data = $result->RetrieveDocumentResult;

		$targetdir = dirname(__FILE__) . '/'.$baseFolder.'doc';
		if(!is_dir($targetdir))
			mkdir($targetdir);
		$filename = md5 ( rand() * time() ) . '.pdf' ;	  

		file_put_contents($targetdir.'/'.$filename, base64_decode($data));

		// Log out (closes connection to backend server)

		$soap->LogOut();

		unset($soap);

		// Return a URL to the file
		$s = $_SERVER['REQUEST_URI'] ;
		$p = strrpos($s,'/') ;
		if ($p) {
			$s = substr($s,0,$p) ;
		}

		$retdata .= "Cmd_XRdocpdf_back('" . $retURL . "','https://" . $_SERVER['HTTP_HOST'] . $s . "/doc/" . $filename . "');" ;
	}
}
else if ( $method == 'XMLPOST' )
{
	// Grab the posted params...
	$url = $_POST['url'] ;
	$dataXML = $_POST['dataXML'] ;
	$varSuccess = $_POST['varok'] ;

	// POST
	$contents = PostToHost($url,'dataXML='.$dataXML);

	if ( ! $contents )
		$isOK = '0' ;
	else if ( ! preg_match("/HTTP\\/1\\...200/i", $contents) ) // Look for ::  HTTP/1.1 200
		$isOK = '0' ;
	else
		$isOK = '1' ;
	$retdata = "Cmd_XMLfetch_back('" . $varSuccess . "'," . $isOK .");" ;
}
else if ( $method == 'UPLOAD' )
{
	$r = array("error" => true,
		"error_text" => "",
		"uploads" => array());

	$pageURL = 'http';
	if ($_SERVER["HTTPS"] == "on")
		$pageURL .= "s";
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80")
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	else
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

	$i = strrpos($pageURL,"/");
	if ( $i !== false )
		$pageURL = substr($pageURL,0,$i+1) ;

	if ( !file_exists(UPLOAD_PATH) )
		mkdir(UPLOAD_PATH);

//	var_dump ($_FILES);

	foreach ($_FILES as $ctrlname => $upfile){	
		if (strlen($upfile["name"]) > 0){	
			if ($upfile["size"] > UPLOAD_MAXSIZE){
				$r["error_text"] = "File too large!" ;
				$retdata = json_encode($r);
				exit;
			}
	    	if ($upfil["error"] > 0){
				$r["error_text"] = "Error code: " . $upfil["error"];
				$retdata = json_encode($r);
				exit;
			}
			do {
				$num = dechex(rand(1,100000000));
				$fn = "file" . sprintf ('%08s', $num) . "." . pathinfo($upfile["name"], PATHINFO_EXTENSION); 
				$uploadFn = UPLOAD_PATH . "/" . $fn ;
			} while ( file_exists($uploadFn) );
			
	    	move_uploaded_file($upfile["tmp_name"], $uploadFn);
	    	$upload = array (
	    		"ctrlname" => $ctrlname,
				"url" => $pageURL.$uploadFn);

	    	$uploads[] = $upload;
	    }
    }

//    var_dump ($uploads);

    $r["error"] = false ;
    $r["uploads"] = $uploads;
	$retdata = json_encode($r);
}
else
	$retdata = "alert('unknown AJAX method :" . $method . "');" ;

echo $retdata ;  
?>