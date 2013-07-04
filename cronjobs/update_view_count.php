<?php
/**
 * @package nxcPromipool 
 * @author  spi@nxc.no <Alex Pilyavskiy>
 * @date    26 Jun 2013
 **/

$viewGAINI = eZINI::instance( 'nxc_views_from_ga.ini' );
$classList = $viewGAINI->variable( 'General', 'ClassList' );
$attributeIdentifier = $viewGAINI->variable('General', 'AttributeIdentifier');
$accData = $viewGAINI->variable('General', 'GAData');
$limit = 50;
$offset = 0;
$ga = new gapi($accData['email'],$accData['password']);
$eZPFetchArray = array();
$eZPFetchArray['Depth'] = 0;
$eZPFetchArray['ClassFilterType'] = 'include';
$eZPFetchArray['ClassFilterArray'] = $classList;

if ( !$isQuiet )
{
    $cli->output( "Updating views count from Google Analitics" );    
}
$nodesCount = eZContentObjectTreeNode::subTreeCountByNodeID($eZPFetchArray,2);
$eZPFetchArray['Limit'] = $limit;
while ($offset < $nodesCount) {
    $eZPFetchArray['Offset'] = $offset;
    $nodes = eZContentObjectTreeNode::subTreeByNodeID($eZPFetchArray, 2);
    $urlArray = array();
    foreach($nodes as $node) {
        $urlArray[] = $node->urlAlias();
    }
    $gaResults = getViewsFromGoogleAnalytics($urlArray, $ga, $accData['profileId'], $cli);    
    foreach( $nodes as $node ) {        
        if (isset($gaResults["/".$node->urlAlias()])) {
            $nodeDM = $node->DataMap();
            if (!isset($nodeDM[$attributeIdentifier]) && !$isQuiet ) {
                $cli->output("No attribute '".$attributeIdentifier."' for node '".$node->urlAlias());
                continue;
            }
            else {
                $nodeDM[$attributeIdentifier]->fromString($gaResults["/".$node->urlAlias()]);
                $nodeDM[$attributeIdentifier]->store();
                $node->store();
                if ( !$isQuiet ) {
                    $cli->output( "Updated views for '".$node->urlAlias()."'; set to '".$gaResults["/".$node->urlAlias()]."'" );
                }
            }
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

    return $result;
}

?>