( function( $ ) {

	$( document ).ready( function() {

		var $requiredInput 		= $( 'select[name=required]' );
		var $visibilityInput 	= $( 'select[name=default-visibility]' );
		var $valueInput 		= $( 'input[name=fieldvalue]' );
		var $allowedInput    	= $( '#allow-custom-visibility-allowed' )
		var $disabledInput   	= $( '#allow-custom-visibility-disabled' );
		var $typeInput  		= $( 'select[name=fieldtype]' );
		var $typeCurrent 		= $( 'select[name=fieldtype]' ).val();
		var $typeNotice 		= $( '#type-change-notice' );
		var $saveField 			= $( 'input[name=saveField]' );
		var $globalVisibility 	= 'global';

		if ( $visibilityInput.val() == $globalVisibility ) {
			$disabledInput.attr( 'checked', true );
			$disabledInput.closest('div').hide();
			$requiredInput.val('0');
			$requiredInput.closest('.postbox').hide();
			$valueInput.closest('.postbox').show();
		}

		$visibilityInput.on( 'change', function() {
			if ( $visibilityInput.val() == $globalVisibility ) {
				$disabledInput.attr( 'checked', true );
				$disabledInput.closest('div').hide();
				$requiredInput.val('0');
				$requiredInput.closest('.postbox').hide();
				$valueInput.closest('.postbox').show();
			} else {
				$valueInput.closest('.postbox').hide();
				$allowedInput.attr( 'checked', true );
				$allowedInput.closest('div').show();
				$requiredInput.closest('.postbox').show();
			}
		});

		$typeInput.on( 'change', function() {
			if ( $typeInput.val() == $typeCurrent ) {
				$typeNotice.hide();
				$valueInput.show();
			} else {
				$typeNotice.show();
				$valueInput.hide();
			}
		});

		$saveField.on( 'click', function() {
			if ( $visibilityInput.val() != $globalVisibility ) {
				$valueInput.val('');
			}
			if ( $typeInput.val() != $typeCurrent ) {
				$valueInput.val('');
			}
		});


	} );

} )( jQuery );