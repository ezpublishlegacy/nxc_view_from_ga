<?php
/**
 * @package nxcPromipool 
 * @author  spi@nxc.no <Alex Pilyavskiy>
 * @date    26 Jun 2013
 **/

$viewGAINI = eZINI::instance( 'nxc_views_from_ga.ini' );
$classList = $viewGAINI->variable( 'General', 'ClassList' );
$parentNodeUrlsList = $viewGAINI->variable( 'General', 'ParentNodeUrl' );
$attributeIdentifier = $viewGAINI->variable('General', 'AttributeIdentifier');
$includeCountsPattern = $viewGAINI->variable('General', 'IncludeCountsPattern');
$googleData = $viewGAINI->variable('General', 'GAData');
$limit = ($viewGAINI->hasVariable('General', 'NodesFetchLimit')) ? $viewGAINI->variable('General', 'NodesFetchLimit') : 50;

// Log in as admin for getting object from all sections (not only standard)
$usr = eZUser::fetch(14);
$usr->loginCurrent();

$cli->setUseStyles( true );
$cli->setIsQuiet($isQuiet);

$cli->output( "Updating views count from Google Analitics" );
$cli->notice( "Limit set to '".$limit."'");

$offset = 0;
$eZPFetchArray = array();
$eZPFetchArray['Depth'] = 0;
$eZPFetchArray['ClassFilterType'] = 'include';
// get google api client
$gaApi = getGoogleClient( $googleData );
foreach( $classList as $class ) {
    $eZPFetchArray['ClassFilterArray'] = array($class);
    $cli->notice( "Processing class '".$class."'");

    $nodesCount = eZContentObjectTreeNode::subTreeCountByNodeID($eZPFetchArray,2);
    $eZPFetchArray['Limit'] = $limit;
    if (isset($parentNodeUrlsList[$class])) {
        $gaResults = getViewsFromGoogleAnalytics( array($parentNodeUrlsList[$class]), $gaApi, $googleData['profile_id'], $cli);            
    }
    while ($offset < $nodesCount) {
        $cli->notice( "Current offset is '".$offset."'");
        $eZPFetchArray['Offset'] = $offset;
        $nodes = eZContentObjectTreeNode::subTreeByNodeID($eZPFetchArray, 2);
        
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
                //$cli->warning( "No views for '".$node->urlAlias()."'");            
            }
        }

        $offset += $limit;
    }
}

function getViewsFromGoogleAnalytics( $urlArray, $ga, $profileId, $cli ) {
    
    $filter = "ga:pagePath=~^/".implode(" || ga:pagePath=~^/",$urlArray);
    $result = array();
    
    try {
        $results = $ga->data_ga->get(
        'ga:'.$profileId,
        '2013-06-01',
        date("Y-m-d"),
        'ga:visits',
        array(
            'dimensions'    => 'ga:pagePath',
            'sort'          => 'ga:pagePath',
            'filters'       => $filter
            )
        );            
    }
    catch (Exception $e) {
        $cli->output($e->getMessage());
    }
    
    if ($results->getRows()) {        
        foreach( $results->getRows() as $ga_result )  {            
            $pagePath = $ga_result[0];
            $pageViews = $ga_result[1];
            $result = array_merge($result, array($pagePath => $pageViews));
        }        
        ksort($result);
    }    
    
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

function getGoogleClient( $gaData ) {
 
    $clientId       = $gaData['client_id'];
    $serviceEmail   = $gaData['service_email'];
    $keyPath        = $gaData['path_to_key'];
    $analyticsScope = 'https://www.googleapis.com/auth/analytics.readonly';
 
    $client = new Google_Client();
    $client->setApplicationName( 'Analytics' );
 
    $client->setClientId( $clientId );
    $client->setAccessType( 'offline_access');  
 
    $client->setAssertionCredentials(
        new Google_AssertionCredentials(
            $serviceEmail,
            array( $analyticsScope ),
            file_get_contents( $keyPath )
            )
        );
 
    $client->setUseObjects( true );        
 
    // create service
    $service = new Google_AnalyticsService( $client );    
    return $service;
 
}

?>