<?php
# php_arXiv_parsing_example.php
#
# This sample script illustrates a basic arXiv api call
# followed by parsing of the results using the Simplepie
# module.
#
# Please see the documentation at
# http://export.arxiv.org/api_help/docs/user-manual.html
# for more information, or email the arXiv api
# mailing list at arxiv-api@googlegroups.com.
#
# Simplepie can be gotten from http://simplepie.org/
#
# Author: Julius B. Lucks, Bill Flanagan
#
# This is free software.  Feel free to do what you want
# with it, but please play nice with the arXiv API!
/** Error reporting */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Europe/London');

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

include_once('./PHPExcel_1.8.0_doc/Classes/PHPExcel.php');
include_once('./simplepie/autoloader.php');

echo date('H:i:s') , " Create new PHPExcel object" , EOL;
$objPHPExcel = new PHPExcel();

// Set document properties
echo date('H:i:s') , " Set document properties" , EOL;
$objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                             ->setLastModifiedBy("Maarten Balliauw")
                             ->setTitle("Office 2007 XLSX Test Document")
                             ->setSubject("Office 2007 XLSX Test Document")
                             ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                             ->setKeywords("office 2007 openxml php")
                             ->setCategory("Test result file");




define ('EOL', "<br />\n");
# Base api query url
$base_url = 'http://export.arxiv.org/api/query?';
# Search parameters
// $search_query = 'all:electron'; # search for electron in all fields
$search_query = 'cs.PL'; # search for cs papers.
$start = 0;                     # retreive the first 5 results
$max_results = 1000;
# Construct the query with the search parameters
$query = "search_query=".$search_query."&start=".$start."&max_results=".$max_results;
# SimplePie will automatically sort the entries by date
# unless we explicitly turn this off
$feed = new SimplePie($base_url.$query);
$feed->enable_order_by_date(false);
$feed->init();
$feed->handle_content_type();
# Use these namespaces to retrieve tags
$atom_ns = 'http://www.w3.org/2005/Atom';
$opensearch_ns = 'http://a9.com/-/spec/opensearch/1.1/';
$arxiv_ns = 'http://arxiv.org/schemas/atom';
# print out feed information
print("Feed information".EOL);
print("Feed title: ".$feed->get_title().EOL);
$last_updated = $feed->get_feed_tags($atom_ns,'updated');
print("Last Updated: ".$last_updated[0]['data'].EOL.EOL);
# opensearch metadata such as totalResults, startIndex,
# and itemsPerPage live in the opensearch namespase
print("<b>Opensearch metadata such as totalResults, startIndex, and itemsPerPage live in the opensearch namespase</b>".EOL);
$totalResults = $feed->get_feed_tags($opensearch_ns,'totalResults');
print("totalResults for this query: ".$totalResults[0]['data'].EOL);
// $startIndex = $feed->get_feed_tags($opensearch_ns,'startIndex');
// print("startIndex for these results: ".$startIndex[0]['data'].EOL);
// $itemsPerPage = $feed->get_feed_tags($opensearch_ns,'itemsPerPage');
// print("itemsPerPage for these results: ".$itemsPerPage[0]['data'].EOL.EOL);
# Run through each entry, and print out information
# some entry metadata lives in the arXiv namespace
// print("<b>Run through each entry, and print out information some entry metadata lives in the arXiv namespace</b>".EOL);
$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0,1, 'id')
                              ->setCellValueByColumnAndRow(1,1, 'title')
                              ->setCellValueByColumnAndRow(2,1, 'published')
                              ->setCellValueByColumnAndRow(3,1, 'author1')
                              ->setCellValueByColumnAndRow(4,1, 'affil1')
                              ->setCellValueByColumnAndRow(5,1, 'author2')
                              ->setCellValueByColumnAndRow(6,1, 'affil2')
                              ->setCellValueByColumnAndRow(7,1, 'author3')
                              ->setCellValueByColumnAndRow(8,1, 'affil3')
                              ->setCellValueByColumnAndRow(9,1, 'author4')
                              ->setCellValueByColumnAndRow(10,1, 'affil4');


$i = 2;
foreach ($feed->get_items() as $entry) {
    // print("<b>Entry ".$i++."</b>".EOL);
    // print("e-print metadata".EOL);
    $temp = split('/abs/',$entry->get_id());
    print("arxiv-id: ".$temp[1].EOL);
    print("Title: ".$entry->get_title().EOL);
    $published = $entry->get_item_tags($atom_ns,'published');
    print("Published: ".$published[0]['data'].EOL);
    # gather a list of authors and affiliation
    #  This is a little complicated due to the fact that the author
    #  affiliations are in the arxiv namespace (if present)
    # Manually getting author information using get_item_tags
    $authors = array();

    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0,$i, $temp[1])
                              ->setCellValueByColumnAndRow(1,$i, $entry->get_title())
                              ->setCellValueByColumnAndRow(2,$i, $published[0]['data']);
    $j = 3;
    foreach ($entry->get_item_tags($atom_ns,'author') as $author) {
        $name = $author['child'][$atom_ns]['name'][0]['data'];
        $affils = array();
        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($j,$i,$name) ;
        # If affiliations are present, grab them

        
        if ($author['child'][$arxiv_ns]['affiliation']) {
            foreach ($author['child'][$arxiv_ns]['affiliation'] as $affil) {
                array_push($affils,$affil['data']);
            }
            if ($affils) {
                $affil_string = join(', ',$affils);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($j+1,$i,$affil_string);
                $name = $name." (".$affil_string.")";
            }
        }
        array_push($authors,'    '.$name.EOL);
        $j=$j+2;
    }
    $author_string = join('',$authors);
    print("Authors: ".EOL.$author_string.EOL);

    
    $i++;
}

// Rename worksheet
echo date('H:i:s') , " Rename worksheet" , EOL;
$objPHPExcel->getActiveSheet()->setTitle('Formulas');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Save Excel 2007 file
echo date('H:i:s') , " Write to Excel2007 format" , EOL;
$callStartTime = microtime(true);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

//
//  If we set Pre Calculated Formulas to true then PHPExcel will calculate all formulae in the
//    workbook before saving. This adds time and memory overhead, and can cause some problems with formulae
//    using functions or features (such as array formulae) that aren't yet supported by the calculation engine
//  If the value is false (the default) for the Excel2007 Writer, then MS Excel (or the application used to
//    open the file) will need to recalculate values itself to guarantee that the correct results are available.
//
//$objWriter->setPreCalculateFormulas(true);
$objWriter->save(str_replace('.php', '.xlsx', __FILE__));
$callEndTime = microtime(true);
$callTime = $callEndTime - $callStartTime;

echo date('H:i:s') , " File written to " , str_replace('.php', '.xlsx', pathinfo(__FILE__, PATHINFO_BASENAME)) , EOL;
echo 'Call time to write Workbook was ' , sprintf('%.4f',$callTime) , " seconds" , EOL;
// Echo memory usage
echo date('H:i:s') , ' Current memory usage: ' , (memory_get_usage(true) / 1024 / 1024) , " MB" , EOL;


// Save Excel 95 file
echo date('H:i:s') , " Write to Excel5 format" , EOL;
$callStartTime = microtime(true);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save(str_replace('.php', '.xls', __FILE__));
$callEndTime = microtime(true);
$callTime = $callEndTime - $callStartTime;

echo date('H:i:s') , " File written to " , str_replace('.php', '.xls', pathinfo(__FILE__, PATHINFO_BASENAME)) , EOL;
echo 'Call time to write Workbook was ' , sprintf('%.4f',$callTime) , " seconds" , EOL;
// Echo memory usage
echo date('H:i:s') , ' Current memory usage: ' , (memory_get_usage(true) / 1024 / 1024) , " MB" , EOL;


// Echo memory peak usage
echo date('H:i:s') , " Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB" , EOL;

// Echo done
echo date('H:i:s') , " Done writing files" , EOL;
echo 'Files have been created in ' , getcwd() , EOL;


?>