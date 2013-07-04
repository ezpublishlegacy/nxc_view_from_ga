<?php

class eZFlowMostVisitedFetch implements eZFlowFetchInterface
{
    public function fetch( $parameters, $publishedAfter, $publishedBeforeOrAt )
    {
        $viewGAINI = eZINI::instance( 'nxc_views_from_ga.ini' );
        $viewsAttributeIdentifier = $viewGAINI->variable('General', 'AttributeIdentifier');
        if ( isset( $parameters['Source'] ) )
        {
            $nodeID = $parameters['Source'];
            $node = eZContentObjectTreeNode::fetch( $nodeID, false, false ); // not as an object            
        }
        else
        {
            $nodeID = 0;
        }

        $subTreeParameters = array();
        $subTreeParameters['AsObject'] = false;
        $subTreeParameters['SortBy'] = array();

        if ( isset( $parameters['Classes'] ) )
        {            
            foreach(explode( ',', $parameters['Classes'] ) as $class) {
                $subTreeParameters['SortBy'][] = array( 'attribute', false,  $class.'/'.$viewsAttributeIdentifier ); // first the latest                
            }
            $subTreeParameters['ClassFilterType'] = 'include';
            $subTreeParameters['ClassFilterArray'] = explode( ',', $parameters['Classes'] );
        }
        
        // Do not fetch hidden nodes even when ShowHiddenNodes=true
        $subTreeParameters['AttributeFilter'] = array( 'and', array( 'visibility', '=', true ) );        
        
        $nodes = eZContentObjectTreeNode::subTreeByNodeID( $subTreeParameters, $nodeID );
        
        if ( $nodes === null )
            return array();        
        
        $fetchResult = array();
        foreach( $nodes as $node )
        {
            $fetchResult[] = array( 'object_id' => $node['contentobject_id'],
                                    'node_id' => $node['node_id'],
                                    'ts_publication' => $node['published']);
        }                

        return $fetchResult;
    }
}

?>
