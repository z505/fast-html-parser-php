<?php
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
// Filename: parser.php                                                       //
// Class: classParser                                                         //
// Function: HTML Parser                                                      //
// Version: 1.2                                                               //
// Author: Jazarsoft                                                          //
// How to:                                                                    //
// -> Create the class                                                        //
//      require_once("parser.php");                                           //
//      $parser = new classParser;                                            //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////

function getTagName($buffer)
{
	if (eregi ("[< ]([A-Za-z0-9]{1,})", $buffer, $out))
	{
		return $out[1];
	}
}

function GetTagAttribute($tag,$attribute)
{
	$nullpos    = 0;
	$quoted     = false;
	$utag       = strtoupper($tag);
	$uattribute = strtoupper($attribute);
	$attrpos    = strpos($utag,$uattribute);

	if ($attrpos === false)
	{
	} else
	{
		for ($count=$attrpos;$count<=strlen($tag)-1;$count++)
		{
			$char = substr($tag,$count,1);
			if ($char=="\"")
			{
					if (!$quoted) 
					{ $got = true; $quoted = true; } 
					else 
					{ $quoted = false;}
			}

			if (ereg("[ \f\n\r\t\v]",$char,$reg) && (!$quoted))
			{
				if ($got)
				{
					$nulpos = $count-1; break;
				}
			}
			else if (($char==">") )
			{
					$nulpos = $count-1; break;
			}
		}
		return substr($tag,$attrpos,($nulpos-$attrpos)+1);
	}
}

function ExtractQuotedStr($str,$quote)
{
	$result="";
	$startpos=strpos($str,$quote);
	if ($startpos===false) { return $str;}
	for ($index=$startpos+1;$index<=strlen($str);$index++)
	{
		 $char=substr($str,$index,1);
		 if ($char!=$quote)
		 {
				$result .= $char;
		 } else
		 {
				Break;
		 }
	}
	return $result;
}

function ExtractValue($attribute)
{
	$result = "";
	$quoted = false;
	$str = $attribute;
	$startpos = strpos($attribute,"=");
	for($count=$startpos+1;$count<=strlen($attribute)-1;$count++)
	{
		 $char=substr($attribute,$count,1);
		 if ( ($char!="\"") or ( (!$quoted) and ($char!=" ")) )
		 {
				$result.=$char;
		 } else
		 {
			 if ( ($char="\"") and (!$quoted) )
			 {
					$quoted=true;
					break;
			 }
		 }
	}
	return ExtractQuotedStr($result,"\"");
}

	class modHTML{
		var $name           = null;          // name
		var $match          = null;          // holds match string (preg_match)
		var $replace        = null;          // holds replace string (preg-replace)
		var $function				= null;          // eval this funtion pass string retieve modified string
		var $path           = null;          // path of scanned file
		var $type           = null;          // allowed: 
                                         //    "r+":  replace multiple times (default)
                                         //    "r" :  replace once 
                                         //    "s" :  search once 
                                         //    "f" :  call function 

		/**
		* @return modHTML
		* @param $name string
		* @param $search string
		* @param $match string
		* @param $replace string
		* @param $function string
		* @param $type string
		* @desc Enter description here...		
 */
		function modHTML($name, $search, $match, $replace, $function = null, $path = "" , $fileName = "", $type = "r+" ){
			$this->name      = $name;
			$this->search    = $search;
			$this->match     = $match;
			$this->replace   = $replace;
			$this->function  = $function;
			$this->path      = $path;
			$this->fileName  = $fileName;
			$this->type      = $type;
		}
	}
	
	class classParser{
		var $html              = "";
		var $ontagfound        = "";
		var $ontextfound       = "";
		var $parse						 = true;
		var $myArrays          = array();
			
			function classParser(){
				$myArrays['elements'][0]  = array();
			}

			function InsertHTML($htmlcode)
			{
				$this->html = "";
				$this->html=$htmlcode;
				return true;
			}

//			function LoadHTML($filename)
			function LoadHTML(&$menu)
			{
				if($this->parse == true)  // parse original file
					return $this->LoadFile($this->html,$menu->fname);
				else{                     // load parsed files
					$f =$menu->tmpPath.'/'.$menu->fileName.".body";
					$this->myArrays['body'][0] = array();
					$this->LoadFile($this->myArrays['body'][0][0],$menu->tmpPath.'/'.$menu->fileName.".body");
					$this->myArrays['title'][0] = array();
					$this->LoadFile($this->myArrays['title'][0][0],$menu->tmpPath.'/'.$menu->fileName.".title");
				}
				return false;
			}

			function LoadFile(&$buffer, $filename)
			{
				if (!file_exists ($filename))
				{
						return false;
				}

				$fh = fopen ($filename, "r");
				if ($fh!=false)
				{
					flock($fh,2);
					while (!feof ($fh))
					{
						$buffer .= fgets($fh, 10240);
					}
					flock($fh,3);
					fclose($fh);
					return true;
				}
				else return false;
			}

			function GetElements(&$result)
			{
				 if (count($this->myArrays['elements'])==0) { return false; $result=array();  }
				 $result=$this->myArrays['elements'];
				 return true;
			}

			function Parse(){
				$intag    = false;
				$tagdepth = 0;
				$text     = "";
				$tag      = "";
				$endTag   = "";
				$str      = "";
				
				if ($this->html=="") return false;
				if ($this->parse == false) return true;
				$len = strlen($this->html);
				$str = &$this->html;
				 
				for ($i=0;$i <= $len;$i++){
					if (($str[$i]=="<") && (!$intag)){
						if( strncmp("<!--",substr($str, $i, 3),3) == 0 ){          // comment ?
							$pos = strpos($str, "-->",$i);
							if($pos !== false){
								$comment = substr($str, $i, $pos-$i+3); 
								$this->myArrays['elements'][0][]=$comment;
							}
							$tag = "";
							$i += $pos-$i+2;
							continue;		 
						}
						if ($text!=""){                          
							$this->myArrays['elements'][0][]=$text;
							$text="";
						}
						$intag = true;
					} 
					else if (($str[$i]==">") && ($intag)){    // end of tag reached tag
						$tag .=">";
						$this->myArrays['elements'][0][] = $tag;
						$intag=false;
						$tag="";
						continue;
					}
					if (!$intag) 
						$text .= $str[$i];
					else if ($intag)
						$tag .= $str[$i];
				}
				return true;
			}

		function copyTag($tagName, $destArrayName, $srcArrayName = "elements"){ 
			if (count($this->myArrays["$srcArrayName"][0])==0) return false;
			if(!isset($this->myArrays["$destArrayName"]))
				$this->myArrays["$destArrayName"][0] = array();
			else $this->myArrays["$destArrayName"][] = array();
			
			$destArray = &$this->myArrays["$destArrayName"][count($this->myArrays["$destArrayName"])-1]; 
			$srcArray  = &$this->myArrays["$srcArrayName"][0];
			for($i=0, $insideTag=false; $i < count($srcArray);$i++){
				if(preg_match("|\s*<\s*$tagName|",$srcArray[$i])){
					$destArray[]=$srcArray[$i];
				}
			}
			return false;
		}
		
		function copyMetaTagContent($tagName, $destArrayName, $srcArrayName = "elements"){ 
			if (count($this->myArrays["$srcArrayName"][0])==0) return false;
			if(!isset($this->myArrays["$destArrayName"]))
				$this->myArrays["$destArrayName"][0] = array();
			else $this->myArrays["$destArrayName"][] = array();
			
			$destArray = &$this->myArrays["$destArrayName"][count($this->myArrays["$destArrayName"])-1]; 
			$srcArray  = &$this->myArrays["$srcArrayName"][0];
			for($i=0, $insideTag=false; $i < count($srcArray);$i++){
				if(preg_match("|\s*<\s*meta\s*name\s*=\s*['\"]*".$tagName."['\"]*\s(.*)|i",$srcArray[$i])){
					$destArray[]=$srcArray[$i];
				}
			}
			return sizeof($destArray);
		}

		function copyTagContent($tagName, $destArrayName, $stopTag= "", $srcArrayName = "elements"){ 
			$stopTagLen = strlen($stopTag);
			if (count($this->myArrays["$srcArrayName"][0])==0) return false;
			if(!isset($this->myArrays["$destArrayName"]))
				$this->myArrays["$destArrayName"][0] = array();
			else $this->myArrays["$destArrayName"][] = array();
			
			$destArray = &$this->myArrays["$destArrayName"][count($this->myArrays["$destArrayName"])-1]; 
			$srcArray  = &$this->myArrays["$srcArrayName"][0];
			for($i=0, $insideTag=false, $tagFound=false; $i < count($srcArray);$i++){
				if( $insideTag ){
					if($srcArray[$i][0] == '<' && preg_match("|<\s*\/\s*$tagName|",$srcArray[$i])){
						$insideTag = false;
						return true;
					}
					else 
						 $destArray[]=$srcArray[$i];
				}
				else if(preg_match("|\s*<\s*$tagName|",$srcArray[$i])){
					$insideTag = true;
					$tagFound=true;
					continue;
				}
				else if($stopTagLen && $tagFound && !$insideTag){
					if(preg_match("|\s*<\s*$stopTag|",$srcArray[$i])) return true;
				}
			}
			return false;
		}
		
		function printTagContent($tagArrayName, $index = 0){ 
			$rc =0;
			$tagArray = &$this->myArrays["$tagArrayName"][$index]; 
			for($i=0, $len=0; $i < count($tagArray);$i++){
				$len += strlen($tagArray[$i]);
//				eval("\$str = \"$tagArray[$i]\";");
//				echo $str;
				echo $tagArray[$i];
				if($len > 80){
					echo "\n\r"; // new line after 80 chars
					$len=0;
				}
			}
			return count($tagArray);
		}

		function writeTagContent($tagArrayName, $fileHandle = 0, $index = 0){ 
			$rc =0;
			if(!fileHandle) return null;
			
//				$rc = fwrite($fileHandle,"<$tagArrayName>\n");
			$tagArray = &$this->myArrays["$tagArrayName"][$index]; 
				if(is_array($tagArray))
					$str = implode($tagArray);
				else 
					$str = $tagArray;
				$rc = fwrite($fileHandle,$str);
				$rc = fwrite($fileHandle, "\n");
/*
			for($i=0; $i < count($tagArray);$i++){
				$rc = fwrite($fileHandle,$str);
				$rc = fwrite($fileHandle,$tagArray[$i]);
				$rc = fwrite($fileHandle, "\n");
			}
*/
//			$rc = fwrite($fileHandle,"\n</$tagArrayName>\n");
			return count($tagArray);
		}
		
		function getTagStringContent($tagArrayName, $index = 0){ 
			$tagArray = &$this->myArrays["$tagArrayName"][$index]; 
			if(isset($tagArray)) return implode($tagArray," ");
			return null;
		}

		function modifydHTML($tagArrayName, &$mod, $index = null){ 
			if(count($this->myArrays["$tagArrayName"]) == 0) return false;
			if(!is_a($mod,"modHTML")) return false;
			for($ar=0; $ar < count($this->myArrays["$tagArrayName"]);$ar++){ 
				if(isset($index) && ($index != $ar)) continue; 
				$tagArray = &$this->myArrays["$tagArrayName"][$ar]; 
				for($i=0, $len=0; $i < count($tagArray);$i++){
					if($tagArray[$i][0] != "<") continue;
					if(preg_match($mod->search,$tagArray[$i])){
						if($mod->function){   // evaluate function
							$func = '$this->'.$mod->function;
							$str  = $tagArray[$i];
							eval("\$str1 = $func( &\$mod, \$str);");
							$tagArray[$i] = $str1;
						}
						else{ 
							$tagArray[$i] = preg_replace( $mod->match, $mod->replace, $tagArray[$i]); 					
						}
					}
				}
			}
			return count($tagArray);
		}
	function link_a_href(&$mod, $str){
		global $mainMenuMgr;
		global $mainInfoMgr;
    global $HTTP_GET_VARS;	
		static $anchor =1;
		$ar = array();
		$rc = null;
		
//<a href="javascript:MM_showHideLayers('searchResult','','hide')">	

		if (preg_match("|<a\s+.*target\s*=\s*['\|\"]?rightFrame['\|\"]?|",$str)){
			if( preg_match( "|href\s*=\s*['\"](\S*)[\"]|", $str, $ar )){ // fetch filename

				if(strncmp($ar[1], "#", 1))   	// doesn't start with an #										
					$rc = $this->mergePathAndFile($mod->path, $ar[1]);		
				else {													// starts with an # --> anchor on this side	
					$rc = $this->mergePathAndFile($mod->path, $mod->fileName);
				}

				$rc = $mainInfoMgr->getPathIndexFromFileName($rc);
				if(strncmp($ar[1], "#", 1) == 0){   	// starts with an #	
					$rc = $rc.$ar[1];
				}
				$cl ="";
				if( preg_match( "|<a\s+.*class\s*=\s*['\|\"]?(\S*)['\|\"]?.*>|i", $str, $ar ) && strlen($ar[1])){ // class defined?
					$cl = ' class="'.$ar[1].'"';
				}
				if(strlen($rc))
					$rc = '<a href="javascript:checkdata(\'info\',\''.$rc.'\')"'.$cl.'>';
//					$rc = '<a name="i'.$anchor.'" href="'.$GLOBALS['HTTP_SERVER_VARS']['PHP_SELF'].'?level='.$HTTP_GET_VARS['level'].'&info='.$rc.'">';
				return $rc;
			}
		}
		else if (preg_match("|<a\s+.*target\s*=\s*['\|\"]mainFrame['\|\"]|",$str)){
			if( preg_match( "|href\s*=\s*['\|\"](\S*)['\|\"]|", $str, $ar )){ // fetch filename
				if(strncmp($ar[1], "#", 1))   	// doesn't start with an #										
					$rc = $this->mergePathAndFile($mod->path, $ar[1]);		
				else {													// starts with an # --> anchor on this side	
					$rc = $this->mergePathAndFile($mod->path, $mod->fileName);
				}
				$rc = $mainMenuMgr->getPathIndexFromFileName($rc);
				$rc = '<a href="javascript:checkdata(\'level\',\''.$rc.'\')">';
				return $rc;
			} 
		}
		else  {
			if( preg_match( "|href\s*=\s*['\|\"](\S*)['\|\"]|", $str, $ar )){ // fetch filename
				if(preg_match( "|href\s*=\s*['\|\"](\S*)(#\S*)['\|\"]|", $str, $ar1 )&& strlen($ar1[1])) $ar = $ar1; 
				if(strncmp($ar[1], "http://", 7) == 0) {  	// global link
					return $str;									
				}
				elseif (preg_match( "|mailto:(\S*)|", $ar[1]) ){ // mail address
					return $str;
				}
				elseif(strncmp($ar[1], "#", 1)){   	// doesn't start with an #										
					$rc = $this->mergePathAndFile($mod->path, $ar[1]);		
				}		
				else {													// starts with an # --> anchor on this side	
					$rc = $this->mergePathAndFile($mod->path, $mod->fileName);
				}

				$rc = $mainMenuMgr->getPathIndexFromFileName($rc);
				if(strncmp($ar[1], "#", 1) == 0){   	// starts with an #	
					return '<a href="javascript:checkdata(\'jump\',\''.$ar[1].'\')">';
				}
				if(strlen($rc))
					$rc = '<a href="javascript:checkdata(\'level\',\''.$rc.$ar[2].'\')">';
				else 
					$rc = preg_replace( $mod->match, $mod->replace, $str);
				return $rc;
			}
		}
	}	
	
	function mergePathAndFile($path, $fn){
		$a_path = explode('/', preg_replace("|\\\\|",    '/',   $path));
		$a_fn   = explode('/', preg_replace("|\\\\|",    '/',   $fn));
		if((count($a_fn) > 1) && (strlen($a_fn[0]) == 0)){  // $fn starts with /
			array_shift($a_fn);
			$rc = implode("/",$a_fn);
			return $rc;
		} 
		if(strlen($a_path[count($a_path)-1]) == 0) 
			array_pop($a_path);										// ..../lklk/ last / creates empty item-->remove
		for($i=0;$i < count($a_fn);$i++){
			if(strncmp($a_fn[$i], "..",2) == 0) array_pop($a_path); 						// remove last directory 
			elseif($a_fn[$i] == ".") continue;      // 
			else $a_fn1[] = $a_fn[$i];
		}
		$rc = array_merge($a_path, $a_fn1);
		$rc = implode("/", $rc);
		return $rc;
	}
	function linkRightFrame(&$mod, $str){
	global $mainInfoMgr;
    global $HTTP_GET_VARS;	
		static $anchor =1;
		$ar = array();
//                 <a href="/modules/physics/brown/boyle.htm" target="rightFrame" class=ahelp>
//  if( preg_match( "|href\s*=\s*['\|\"][\\\/](\S*)['\|\"]|", $str, $ar )){ // fetch filename
		if( preg_match( "|href\s*=\s*['\"](\S*)[\"]|", $str, $ar )){ // fetch filename
			$rc = $mainInfoMgr->getPathIndexFromFileName($ar[1]);
//			$rc = '<a name="i'.$anchor.'" href="'.$GLOBALS['HTTP_SERVER_VARS']['PHP_SELF'].'?level='.$HTTP_GET_VARS['level'].'&info='.$rc.'.i'.$anchor++.'">';
			$rc = '<a name="i'.$anchor.'" href="'.$GLOBALS['HTTP_SERVER_VARS']['PHP_SELF'].'?level='.$HTTP_GET_VARS['level'].'&info='.$rc.'">';
			return $rc;
		}
	}	

	function linkMainFrame(&$mod, $str){
	global $mainMenuMgr;
 		$ar = array();
//                 <a href="/modules/physics/brown/boyle.htm" target="mainFrame" class=ahelp>
//		if( preg_match( "|href\s*=\s*['\|\"][\\\/](\S*)['\|\"]|", $str, $ar )){ // fetch filename
		if( preg_match( "|href\s*=\s*['\|\"](\S*)['\|\"]|", $str, $ar )){ // fetch filename
			$rc = $mainMenuMgr->getPathIndexFromFileName($ar[1]);
			$rc = '<a href="'.$GLOBALS['HTTP_SERVER_VARS']['PHP_SELF'].'?level='.$rc.'">';
			return $rc;
		}
	}	
}// end classParser

?>
