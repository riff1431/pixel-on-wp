jQuery(document).ready(function($) {


    function handleBulkClick(event, selectId) {
        var action = $(selectId).val();
        
        if (action === 'pixelonwp_send_whatsapp') {
            event.preventDefault(); // Stop form submission
            
            var linksToOpen = [];
            
            // Find all checked checkboxes in the table
            $('#the-list th.check-column input[type="checkbox"]:checked, #the-list td.check-column input[type="checkbox"]:checked').each(function() {
                var $row = $(this).closest('tr');
                var $waBtn = $row.find('.pixelonwp-wa-btn');
                
                if ($waBtn.length > 0) {
                    var link = $waBtn.attr('data-wa-link');
                    if (link) {
                        linksToOpen.push(link);
                    }
                }
            });

            if (linksToOpen.length > 0) {
                var $btn = $(event.currentTarget);
                var originalText = $btn.val() || 'Apply';
                $btn.val('Opening Tabs...').prop('disabled', true);
                
                var i = 0;
                var interval = setInterval(function() {
                    if (i < linksToOpen.length) {
                        window.open(linksToOpen[i], '_blank');
                        i++;
                    } else {
                        clearInterval(interval);
                        $btn.val(originalText).prop('disabled', false);
                    }
                }, 800); // 800ms delay between tabs
            } else {
                alert('No valid WhatsApp numbers found for the selected orders.');
            }
        }
    }

    $('#doaction').on('click', function(e) {
        handleBulkClick(e, 'select[name="action"]');
    });

    $('#doaction2').on('click', function(e) {
        handleBulkClick(e, 'select[name="action2"]');
    });
});
