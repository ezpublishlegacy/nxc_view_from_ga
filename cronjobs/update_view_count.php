<?php
/**
 * @package nxcPromipool 
 * @author  spi@nxc.no <Alex Pilyavskiy>
 * @date    26 Jun 2013
 **/

$viewGAINI = eZINI::instance( 'nxc_views_from_ga.ini' );
$classList = $viewGAINI->variable( 'General', 'ClassList' );
$attributeIdentifier = $viewGAINI->variable('General', 'AttributeIdentifier');
$includeCountsPattern = $viewGAINI->variable('General', 'IncludeCountsPattern');
$accData = $viewGAINI->variable('General', 'GAData');
$limit = ($viewGAINI->variable('General', 'NodesFetchLimit')) ? $viewGAINI->variable('General', 'NodesFetchLimit') : 50;
$offset = 0;
$ga = new gapi($accData['email'],$accData['password']);
$eZPFetchArray = array();
$eZPFetchArray['Depth'] = 0;
$eZPFetchArray['ClassFilterType'] = 'include';
$eZPFetchArray['ClassFilterArray'] = $classList;

// Log in as admin for getting object from all sections (not only standard)
$usr = eZUser::fetch(14);
$usr->loginCurrent();

$cli->setUseStyles( true );
$cli->setIsQuiet($isQuiet);

$cli->output( "Updating views count from Google Analitics" );
$cli->notice( "Limit set to '".$limit."'");

$nodesCount = eZContentObjectTreeNode::subTreeCountByNodeID($eZPFetchArray,2);
$eZPFetchArray['Limit'] = $limit;
while ($offset < $nodesCount) {
    $cli->notice( "Current offset is '".$offset."'");
    $eZPFetchArray['Offset'] = $offset;
    $nodes = eZContentObjectTreeNode::subTreeByNodeID($eZPFetchArray, 2);    
    $urlArray = array();
    foreach($nodes as $node) {        
        $urlArray[] = $node->urlAlias();
    }
    $gaResults = getViewsFromGoogleAnalytics($urlArray, $ga, $accData['profileId'], $cli);
    foreach( $nodes as $node ) {
        $regPattern = false;
        if (isset($includeCountsPattern[$node->classIdentifier()])) {
            $regPattern = "/".$node->urlAlias().'/('.$includeCountsPattern[$node->classIdentifier()].')?';
        }        
        if (isset($gaResults["/".$node->urlAlias()]) || 
            ($regPattern && preg_grep_keys($regPattern, $gaResults)) ) {
                if ($regPattern) { 
                    combineViews( $gaResults, "/".$node->urlAlias(), $regPattern);
                }
                $nodeDM = $node->DataMap();
                if (!isset($nodeDM[$attributeIdentifier])) {
                    $cli->error("No attribute '".$attributeIdentifier."' for node '".$node->urlAlias());
                    continue;
                }
                else {
                    $nodeDM[$attributeIdentifier]->fromString($gaResults["/".$node->urlAlias()]);
                    $nodeDM[$attributeIdentifier]->store();
                    $node->store();
                    $cli->output( "Updated views for '".$node->urlAlias()."'; set to '".$gaResults["/".$node->urlAlias()]."'" );
                }
        }
        else {            
            $cli->warning( "No views for '".$node->urlAlias()."'");            
        }
    }
    
    $offset += $limit;
}

function getViewsFromGoogleAnalytics( $urlArray, $ga, $profileId, $cli ) {    
    $filter = "ga:pagePath=~^/".implode(" || ga:pagePath=~^/",$urlArray);
    $result = array();
    try {
        $ga->requestReportData($profileId,array('pagePath'),array('pageviews'),array(),$filter);
    }    
    catch (Exception $e) {
        $cli->output($e->getMessage());
    }
    
    foreach( $ga->getResults() as $ga_result )  {
        $pagePath = $ga_result->getDimesions();
        $pageViews = $ga_result->getMetrics();
        $result = array_merge($result, array($pagePath['pagePath'] => $pageViews['pageviews']));
    }
    ksort($result);    
    return $result;
}

function combineViews( &$results, $url, $pattern ) {
    $pattern = str_replace('/', '\/', $url);
    $res = preg_grep_keys('/'.$pattern.'/', $results);    
    $results[$url] = 0;
        
    foreach ($res as $rUrl => $rVisits) {
        $results[$url] += $rVisits;
        if ($rUrl != $url) unset($results[$rUrl]);
    }    
    
}

function preg_grep_keys( $pattern, $input, $flags = 0 )
{
    $keys = preg_grep( $pattern, array_keys( $input ), $flags );
    $vals = array();
    foreach ( $keys as $key )
    {
        $vals[$key] = $input[$key];
    }
    return $vals;
}

?>