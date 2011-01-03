<?php 
error_reporting(E_ALL);

$pathToScript = dirname($_SERVER['SCRIPT_NAME']);
$scriptName = basename($_SERVER['SCRIPT_FILENAME']);
$absolutePathToWorkingDir = "http://$_SERVER[SERVER_NAME]$pathToScript/";
$absolutePathToScript = "http://$_SERVER[SERVER_NAME]";

$head = <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> <html xmlns="http://www.w3.org/1999/xhtml"> <head> <link rel="SHORTCUT ICON" href="common images/favicon.ico"/> <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> <title>Duffield Lab</title> <link href="$absolutePathToWorkingDir/index.css" rel="stylesheet" type="text/css" /> <style type="text/css"> </style> </head> <body> <div id="container"> 
EOT;


$frontpageimage = <<<EOT
<div id ="frontpagecontent"> <img src="Front_Page_Images/cover_scaled.png"/> </div>
EOT;

$footer =<<<EOT
<div id="footer"> <a href="http://www.washington.edu/">University of Washington</a> ||<a href="http://depts.washington.edu/iscrm/"> Institute for Stem Cell & Regenerative Medicine</a> ||<a href="$absolutePathToScript/contact"> Contact </a>||<a href="http://intranet.duffieldlab.com"> Lab Intranet</a></div> </div> </body> </html>
EOT;

$path_array = explode("/",$_SERVER['PHP_SELF']);
$protocol_path = "protocols";
$publication_path = "publications";
$people_path = "people";
print($head);
print_Banner();
flush();

if(isset($_GET['page'])){
             switch($_GET['page']){
		case "interests":
			if(isset($_GET['page_topic'])) {
				print "<div class=\"section_front_page\">".print_html_file("{$_GET["page"]}/{$_GET['page_topic']}", "my_file.html")."</div>"; 
			}
			else print "<div class=\"section_front_page\" id=\"interests_front_page\">".printFiles("interests" , 1 , $absolutePathToWorkingDir, "interests")."</div>";
			break;
		case "people":
			if(isset($_GET['page_topic'])) {
				print "<div class=\"content_page\">".print_html_file("$_GET[page]/$_GET[page_topic]/$_GET[page_sub_topic]", "my_file.html")."</div>";
			}
			else print "<div class=\"section_front_page\" id=\"people_front_page\">".printFiles($people_path,1,$absolutePathToWorkingDir, "people")."</div>";
			break;
		case "protocols":
			print "<div class=\"section_front_page\" id=\"protocols_front_page\">".printFiles($protocol_path,1,$absolutePathToWorkingDir, "protocols")."</div>";
			break;
		case "publications":
			if(isset($_GET['page_sub_topic'])) print print_html_file("$_GET[page]/$_GET[page_topic]/$_GET[page_sub_topic]", "my_file.html");
			else {
				print "<div class=\"section_front_page\" id=\"publications_front_page\">".printFiles($publication_path, 2, $absolutePathToWorkingDir, "publications");
				flush();
				htmlRSS("http://eutils.ncbi.nlm.nih.gov/entrez/eutils/erss.cgi?rss_guid=1xajCEEP5yICGwpYypeXvqo2ycAcLhYdA0--fg8WrfRiI8nsQk");
				print "</div>";
			}
			break;
		case "contact":
			print "<div class=\"section_front_page\" id-\"contact_front_page\">".print_html_file("$_GET[page]/content_contact", "my_file.html")."</div>";
			break;
	}
}

else {print($frontpageimage);}
print($footer);

function printFiles($path, $level, $siteaddress, $class_name) {

global $pathToScript;
global $absolutePathToScript;
$image_file_match = "/front_.*\.(png|gif|jpg|jpeg)$/";
$pattern = "/^protocol_*/";
$html="";
$ordered_files = array();

	if($dir = opendir($path)){		
		while(true == ($file = readdir($dir))) {
			if (!preg_match("/^\./",$file)) {
				$file_priority_pattern = '/(?<=_)[0-9]+(?=_)/';
				if(preg_match($file_priority_pattern, $file, $file_place) != 0) { 
					$ordered_files[(int) $file_place[0]] = $file;
				}
				else $ordered_files[] = $file;
			}
		}

		ksort($ordered_files);

		foreach ($ordered_files as $file) {
			$relativePath = "$path/$file";	
			if(is_dir($relativePath) && !preg_match("/^content_/",$file)) { 
				$replace_pattern = '/_[0-9]*/';
				$section_title = strtoupper(preg_replace($replace_pattern, " ", basename($relativePath))); 
				$html .= "<h$level class=\"{$class_name}_header\">".$section_title."</h$level>" ;
				$html .= printFiles($relativePath , $level + 1 , $siteaddress, $class_name); 
			}
			else {
				if(preg_match("/^content_/",$file)) {
					$contentTitle = str_replace(array("content_","_"),array(""," "),$file);
					if (file_exists("./$path/$file.png")) $html .=  "<div class=\"{$class_name}_img\"><img src=\"$siteaddress$relativePath.png\"/></div>";
					if (file_exists("$path/$file/front_page_picture.png")) $html .=  "<div class=\"{$class_name}_img\"><img src=\"$siteaddress$relativePath/front_page_picture.png\"/></div>";
					if (file_exists("$path/$file/summary.html")) $html .=  ("<div class=\"summary\" id=\"{$class_name}_summary\">".print_html_file("$path/$file" , "summary.html") . "</div>");
					if (!preg_match("/\.png$/",$file)) $html .=  "<div class=\"${class_name}_title\"><a href=\"$absolutePathToScript/$path/".urlencode(urlencode($file))."\">$contentTitle</a></div>";
				}
				else {
					$fileTitle = basename($relativePath);
					$fileTitle = preg_replace($pattern,"",$fileTitle);
					$fileTitle = substr($fileTitle, 0,strrpos($fileTitle,'.')); 
					$replace_pattern = '/_[0-9]*/';
					$fileTitle = preg_replace($replace_pattern, " ", $fileTitle);
					$html .= "<p><a href=\"$siteaddress$relativePath\">$fileTitle</a></p>"; 
				}
			}
		}
		closedir($dir);
	}
	else $html .="<br>Unable to open $path";
	return $html;
}

function htmlRSS ($rssURL) {

	echo "<h2>Lab Publications</h2>";
	$doc = new DOMDocument();
	$doc->load($rssURL);
	$items = $doc->getElementsByTagName("item");
	foreach($items as $item) {
		$title = $item->getElementsByTagName('title')->item(0)->nodeValue;
		$link = $item->getElementsByTagName('link')->item(0)->nodeValue;
		echo "<h3><a href=\"$link\">$title</a></h3>";
		echo $item->getElementsByTagName('description')->item(0)->nodeValue;
	}
}

function print_html_file($relative_directory_path, $file_name) {

	global $absolutePathToWorkingDir;
	$path_to_html = "$relative_directory_path/$file_name";
	if (false !== $html_string = @file_get_contents($path_to_html)) {
		$html_string = preg_replace("/@@@path_to_html@@@/","$absolutePathToWorkingDir/$relative_directory_path/",$html_string);
		return $html_string;
	}
	else {
		print ("<div id=\"no_content\">No Content</div>");
	}
}

function print_Banner() {

global $absolutePathToScript;

	print("<div class=\"load\" id=\"banner\">");

	$pages = array('Interests'=>array(), 'People'=>array("Principal Investigator", "Grad Students","Post Docs", "Senior Scientists"), 'Publications'=>array("Featured","Lab Publications"), 'Protocols'=>array(), 'Contact'=>array());

	foreach($pages as $page=>$categories){
		print ("<div class=\"menu_element\" id=\"". strtolower($page). "\"><div class=\"menu_links_container\">");
		//print ("<ul class=\"sub_category_list\"><li><a class=\"category_link\" href=\"$absolutePathToScript/". strtolower($page) ."\"\>$page");
		print ("<ul class=\"sub_category_list\"><li><a class=\"category_link\" href=\"$absolutePathToScript/".strtolower($page)."\">$page");
		print ("</a></li></ul></div></div>");
	}

	print ("<div class=\"menu_element\" id=\"logo\"><a id=\"logo_link\" href=\"$absolutePathToScript\"></a></div>");
	print ("</div>");
}
?>
