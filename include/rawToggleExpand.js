<script type="text/javascript">
$(document).ready(function(){

	//Hide (Collapse) the toggle containers on load
	$(".eh_toggle_container").hide(); 

	//Switch the "Open" and "Close" state per click then slide up/down (depending on open/close state)
	$("a.eh_head").click(function(){
		$(this).toggleClass("active").next().slideToggle("slow");
	});

});
</script>