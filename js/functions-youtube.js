////////////////////////
// functions youtube //
//////////////////////

function loadPlayer(){
    player = new YT.Player('player', {
        height: '330',
        width: '640',
        videoId: '',
        playerVars: {
            'autoplay': 1, 
            'controls': 1, 
            'border': 0, 
            'showinfo': 0, 
            'showsearch': 0, 
            'rel': 0
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

function resizePlayer(){
    var newWidth = $("div#containerPlayer").width();
    //var newHeight = newWidth * 0.5625;
    var newHeight = 330;
    player.setSize(newWidth, newHeight);       
}

function loadVideo(p1,p2,video_id){
    voted = false;
    if(isPlaying)
        player.stopVideo();
    
    showTitleScreen(p1,p2);
    
    player.loadVideoById(video_id, 0, "large");
    setTimeout(clearTitleScreen, 3000);
    
    var c=tabVideos[currentVideo+1];
    $("div.nextVideo a").attr("href","#!/video/"+c.video_id+"/"+c.animal1+"/"+c.animal2);
    
    updateShareLinks(video_id,p1,p2);
}

function loadVideoByNumber(newCurrent){
    currentVideo=newCurrent;
    var c=tabVideos[currentVideo];
    loadVideo(c.animal1,c.animal2,c.video_id);
}

function loadVideoFromUrl(p1,p2,video_id){
    currentVideo++;
    loadVideo(p1,p2,video_id);
}

function loadNextVideo(){
    voted=false;    
    currentVideo++;
    var c=tabVideos[currentVideo];
    loadVideo(c.animal1,c.animal2,c.video_id);
}

function stopVideo() {
    player.stopVideo();
}

function showTitleScreen(p1,p2){    
    //clear end si besoin
    clearEndScreen();
    clearLoadingScreen();
    //Maj surimp
    $("div#titlePlayer span").html(p1.toUpperCase()+" V.S. "+p2.toUpperCase());
    $("div#titlePlayer").show();
    //Maj logos
    $("ul#animalsPlayer").html(
        "<li><a data-hover='"+getlogo(p1)+"' href='javascript:vote(1)'><img alt='' src='img/"+getlogo(p1)+"' /></a>"+
        "<span id='scoreP1'>"+p1.toUpperCase()+"</span></li>"+
        "<li>VS<span id='scoreP2'>"+p2.toUpperCase()+"</span><a data-hover='"+getlogo(p2)+"' href='javascript:vote(2)'><img alt='' src='img/"+getlogo(p2)+"' /></a>"+
        "</li>"
        );
        
    $("ul#animalsPlayer li a img").hover(function(){
        $(this).attr("src","img/RONDplus1.png");
    },function(){
        $(this).attr("src","img/"+$(this).parent("a").data("hover"));
    });
}

function showEndScreen(p1,p2){
    //Show surimp
    $("div#endPlayer").show();
}

function getlogo(name){
    for(j in tabAnimals)
        if(tabAnimals[j].name == name)
            return tabAnimals[j].image;    
    return 0;
}

function clearTitleScreen(){
    $("div#titlePlayer").hide();
}
function clearEndScreen(){
    $("div#endPlayer").hide();
}
function clearLoadingScreen(){
    $("div#loadingPlayer").hide();
}
function showLoadingScreen(){
    $("div#loadingPlayer").show();
}

function vote(id){
    if(! voted){
        $("ul#animalsPlayer li a img").unbind('mouseenter');

        $.ajax({
            url: apiUrl+"/vote/"+tabVideos[currentVideo].video_id+"/"+id
        }).done(function(data){
            voted=true;
            //traitement graphique
            //winner
            if(data[0].num_votes > data[1].num_votes)
                $("ul#animalsPlayer li a:first").addClass("winner");
            else
                $("ul#animalsPlayer li a:nth-child(2)").addClass("winner");
            //player select
            if(id == 1)
                $("ul#animalsPlayer li a:first").addClass("select");
            else
                $("ul#animalsPlayer li a:nth-child(2)").addClass("select"); 
            //maj score
            $("span#scoreP1").append(": "+data[0].num_votes+" votes");
            $("span#scoreP2").append(": "+data[1].num_votes+" votes");
        });
    }
}

function toggleAnimals(){
    $("div#moreAnimals").toggle();
    
    $(document).mouseup(function (e){
        //hide menu on click out if there are opened							
        if ($("div#moreAnimals").has(e.target).length === 0 && !$("div#moreAnimals").is(e.target)){            
            $("div#moreAnimals").hide();
        }
    });     
}

function updateShareLinks(id,p1,p2){
    var url=encodeURIComponent('http://www.junglebattle.com/#!/video/'+id+'/'+p1+'/'+p2+'/');
    var text=encodeURIComponent("Look it's a "+p1+" and a "+p2+" fighting!");
    var twitterUrl='https://twitter.com/intent/tweet?text='+text+'&url='+url;
    $("#twitterShare").attr("href",twitterUrl);
    
    var fbUrl='http://www.facebook.com/sharer.php?u='+url+'&t='+text;
    $("#fbShare").attr("href",fbUrl);
    
    var tumblrUrl='http://tumblr.com/share?s=&v=3&t='+text+'&u='+url;
    $("#tumblrShare").attr("href",tumblrUrl);
}