var lup = {

    initialize: function (lupConfig) {
        var elements = $(lupConfig['selector']);

        for (var i = 0; i < elements.length; i++) {
            $(elements[i]).on('click', function (e) {
                e.preventDefault();
                var data = {
                    'action': 'user/photo/remove',
                };

                if (confirm(lupConfig['removeMessage'])) {
                    $.ajax({
                        url: lupConfig['actionUrl'],
                        type: 'POST',
                        dataType: 'json',
                        data: data,
                        success: function (data) {
                            if (data.success === true) {
                                document.location.reload();
                            }
                        }
                    });
                }
            });
        }
    }
};