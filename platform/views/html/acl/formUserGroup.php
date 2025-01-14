<?php

$oAcl = clRegistry::get( 'clAcl' );
$oAcl->aroId = array();
$aAclDataDict = $oAcl->oDao->getDataDict();

if( !empty($_GET['acoKey']) && !empty($_GET['aclType']) && array_key_exists($_GET['aclType'], $aAclDataDict['entAcl']['aclType']['values']) ) {
	$aAclData = array(
		'aroId' => array()
	);
	
	if( !$oUser->oAclGroups->isAllowed('superuser') ) {
		$aUserGroups = array_keys($oUser->oAclGroups->aAcl);
		$aUserGroups = array_combine( $aUserGroups, $aUserGroups );
	} else {
		require_once PATH_FUNCTION . '/fData.php';
		$oUserManager = clRegistry::get( 'clUserManager' );
		$aUserGroups = arrayToSingle( $oUserManager->readGroup(), 'groupKey', 'groupKey' );
	}
	if( isset($aUserGroups['super']) ) unset( $aUserGroups['super'] );
	ksort( $aUserGroups );
	
	$oAcl->setAro( $aUserGroups, 'userGroup' );
	
	if( empty($_POST['aroId']) ) $_POST['aroId'] = array();
	
	if( !empty($_POST['frmAclUserGroupAdd']) ) {
		foreach( $_POST['aroId'] as $key => $value ) {
			if( $value == 'all' ) unset( $_POST['aroId'][ $key ] );
		}	
		$_POST['aroId'] = !empty( $_POST['aroId'] ) ? array_intersect( $_POST['aroId'], $aUserGroups ) : array();
		$oAcl->updateByAco( $_GET['acoKey'], $_GET['aclType'], $_POST['aroId'], 'userGroup' );
		
		$oNotification = clRegistry::get( 'clNotificationHandler' );
		$oNotification->set( array(
			'dataSaved' => _( 'The data has been saved' )
		) );
	}
	
	$aAcl = $oAcl->readByAco( $_GET['acoKey'], array(
		'aclAroId',
		'aclAccess'
	), $_GET['aclType'], 'userGroup' );
	foreach( $aAcl as $entry ) {
		if( in_array($entry['aclAroId'], $aUserGroups) && $entry['aclAccess'] == 'allow' ) $aAclData['aroId'][] = $entry['aclAroId'];
	}
	
	$oOutputHtmlForm = clRegistry::get( 'clOutputHtmlForm' );
	$oOutputHtmlForm->init( $aAclDataDict, array(
		'action' => '',
		'attributes' => array( 'class' => 'marginal' ),
		'data' => $aAclData,
		'labelSuffix' => ':',
		'method' => 'post',
		'buttons' => array(
			'submit' => _( 'Save' )
		)
	) );
	$oOutputHtmlForm->setFormDataDict( array(
		'aroId' => array(
			'type' => 'arraySet',
			'appearance' => 'full',
			'values' => array( 'all' => '<strong>' . _( 'Select all' ) . '</strong>' ) + $aUserGroups,
			'title' => _( 'User groups' )
		),
		'frmAclUserGroupAdd' => array(
			'type' => 'hidden',
			'value' => true
		)
	) );
	
	$sTitle = _( 'Permissions' );
	$sFastLink = '';
	
	switch( $_GET['aclType'] ) {
		case 'view':
			$oView = clRegistry::get( 'clViewHtml' );
			$aData = $oView->read( '*', $_GET['acoKey'] );
			if( !empty($aData) ) {
				$sTitle .= ' ' . _( 'for' ) . ': <span>"' . $aData['viewModuleKey'] . '/' . $aData['viewFile'] . '"<span>';
				$sFastLink = '<hr /><a href="?ajax=true&view=' . $aData['viewModuleKey'] . '/' . $aData['viewFile'] . '" target="_blank" class="icon iconText iconClone">' . _( 'Load view in new tab' ) . '</a>';
			}			
			break;
		case 'layout':
			$oLayout = clRegistry::get( 'clLayoutHtml' );
			$aData = $oLayout->readCustom( '*', $_GET['acoKey'] );
			if( !empty($aData) ) {		
				$sTitle .= ' ' . _( 'for' ) . ': <span>"' . $aData['layoutKey'] . '"<span>';
			}			
			break;
		case 'userGroup':
			break;
		case 'dao':
			$sTitle .= ' ' . _( 'for' ) . ': <span>"' . $_GET['acoKey'] . '"<span>';
			break;
	}
	
	echo '
		<div class="view adminAclAcoAdd">
			<h1>' . $sTitle . '</h1>
			' . $oOutputHtmlForm->render() . '
			' . $sFastLink . '
		</div>';
		
	$oTemplate->addBottom( array(
		'key' => 'selectAllAcl',
		'content' => '
			<script>
				$("#aroIdAll").click( function(event) {
					if( $(this).prop("checked") == true ) {
						$(".adminAclAcoAdd .checkbox").each( function( index, listItem ) {
							$(this).prop( "checked", true );
						} );
					} else {
						$(".adminAclAcoAdd .checkbox").each( function( index, listItem ) {
							$(this).prop( "checked", false );
						} );
					}
				} );
			</script>
		'
	) );
}