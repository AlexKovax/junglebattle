//////////////
// init.js //
////////////

// This code loads the IFrame Player API code asynchronously.
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// This function creates an <iframe> (and YouTube player) after the API code downloads.
var player;
var playlist;
var tabAnimals;
var currentVideo=0;
var tabVideos;
var voted=false;
var p1select="";
var p2select="";

//animal list init
$.ajax({
    url: "http://junglebattle.com/api/animals"
}).done(function(data){
    tabAnimals=data;
    console.log(data);
    var i = 0;
    for(i in tabAnimals){        
        if(i > 6)
            $("div#moreAnimals ul").append("<li class='rank"+i+"'><a class='animalPlayer' data-animal='"+tabAnimals[i].name+"' href='#'><img src='img/"+tabAnimals[i].image+"'/></a></li>")
        else
            $("div#containerAnimals ul").append("<li class='rank"+i+"'><a class='animalPlayer' data-animal='"+tabAnimals[i].name+"' href='#'><img src='img/"+tabAnimals[i].image+"'/></a></li>")
    }
    
    //player selection handling
    $("a.animalPlayer").click(function(e){
        //console.log("hello");
        $("div#moreAnimals").hide();
        
       if(p1select=="" || (p1select!="" && p2select!="")){
           if(p1select!="" && p2select!=""){
               p1select="";
               p2select="";
               $("li").removeClass("p1select p2select");
           }
           
           p1select=$(this).data("animal");
           $(this).parent("li").addClass("p1select");
            $.ajax({
                url: "http://junglebattle.com/api/videos/"+p1select+"/10"
            }).done(function(data){
                tabVideos=data;
                currentVideo=-1;
                loadNextVideo();
            });
       }else if(p1select!="" && p2select==""){
           p2select=$(this).data("animal");
           $(this).parent("li").addClass("p2select");
            $.ajax({
                url: "http://junglebattle.com/api/videos/"+p2select+"/"+p1select+"/10"
            }).done(function(data){
                tabVideos=data;
                currentVideo=-1;
                loadNextVideo();
            });            
        }
        
        e.preventDefault();
    });    
});

function onYouTubeIframeAPIReady() {
    //Jquery to get the first video
    $.ajax({
        url: "http://junglebattle.com/api/videos/10"
    }).done(function(data){
        tabVideos=data;
        showTitleScreen(data[0].animal1,data[0].animal2);
        
        //init (first load)
        player = new YT.Player('player', {
            height: '390',
            width: '640',
            videoId: data[0].video_id,
            playerVars: { 'autoplay': 1, 'controls': 1, 'border': 0, 'showinfo': 0, 'showsearch': 0, 'rel': 0 },
            events: {
                'onReady': onPlayerReady,
                'onStateChange': onPlayerStateChange
            }
        }); 
        
        setTimeout(clearTitleScreen, 5000);
    });
    
}

// 4. The API will call this function when the video player is ready.
function onPlayerReady(event) {
    var newWidth = $("div#containerPlayer").width();
    var newHeight = newWidth * 0.5625; 
    player.setSize(newWidth, newHeight);
               
}

function onPlayerStateChange(event) {
   if (event.data == YT.PlayerState.ENDED)
        console.log("end");
}

function updateDim(){
    //
}

$(document).ready(function(){
    //tips
    $("a.tips").tipTip({
        defaultPosition: "top"
    });
    $("a.hover").hover(function(){
        
    })
    
});

$(window).resize(function(){
    var newWidth = $("div#containerPlayer").width();
    var newHeight = newWidth * 0.5625; 
    player.setSize(newWidth, newHeight); 
});