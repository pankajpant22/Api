/**
 * Created by Pankaj on 2/19/2016.
 */

// Shorthand for $( document ).ready()
$(function() {
    $("#resume").click(function(){
        $('#resume_pdf').bPopup({
            easing: 'easeOutBack', //uses jQuery easing plugin
            speed: 450,
            transition: 'slideDown'
        });;
    });

    $('#iEat').click(function() {
        var id = $(this).attr('id');
        var url = 'http://'+location.host+'/api/project/'+id;
        $.ajax({
            url: url,
            error: function() {
                $('#info').html('<p>An error has occurred</p>');
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var $title = $('<h1>').text(data.talks[0].talk_title);
                    var $description = $('<p>').text(data.talks[0].talk_description);
                    $('#info')
                        .append($title)
                        .append($description);
                }
            },
            type: 'POST'
        });
    });

});