
M.report_kentplayer = {
    Y : null,
    transaction : [],

	init: function (Y) {
        var select = Y.one('#menurole');
        select.on('change', function (e) {
        	var id = e.target.get('value');
        	window.location = M.cfg.wwwroot + "/report/kentplayer/index.php?role=" + id
        });
    }
}