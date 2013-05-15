//////////////
// init.js //
////////////

/////////////////////////
//VAR
var player,
    playlist,
    tabAnimals,
    tabVideos;

var voted=false;
var initDone = false;
var p1select="";
var p2select="";
var nvideos=10;
var currentVideo = 0;
var apiUrl="http://junglebattle.com/api";

//////////////////////////
// FUNCTIONS 
//animal list init
function animalsInit(){
    $.ajax({
        url: apiUrl+"/animals"
    }).done(function(data){
        tabAnimals=data;
        var i = 0;
        for(i in tabAnimals){
            if(i > 6)
                $("div#moreAnimals ul").append("<li class='rank"+i+"'><a class='animalPlayer' data-animal='"+tabAnimals[i].name+"' href='#'><img src='img/"+tabAnimals[i].image+"'/></a></li>")
            else
                $("div#containerAnimals ul").append("<li class='rank"+i+"'><a class='animalPlayer' data-animal='"+tabAnimals[i].name+"' href='#'><img src='img/"+tabAnimals[i].image+"'/></a></li>")
        }

        //CLICK player selection handling
        $("a.animalPlayer").click(function(e){
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
                    url: "http://junglebattle.com/api/videos/"+p1select+"/"+nvideos
                }).done(function(data){
                    tabVideos=data;
                    currentVideo=-1;
                    loadNextVideo();
                });
            }else if(p1select!="" && p2select==""){
                p2select=$(this).data("animal");
                $(this).parent("li").addClass("p2select");
                $.ajax({
                    url: "http://junglebattle.com/api/videos/"+p2select+"/"+p1select+"/"+nvideos
                }).done(function(data){
                    tabVideos=data;
                    currentVideo=-1;
                    loadNextVideo();
                });
            }
            e.preventDefault();
        });
    
        //HOVER
        $("a.animalPlayer").hover(function(){
            if(p1select=="" || (p1select!="" && p2select!="")){
                $(this).parent("li").toggleClass("p1select");
            }else if(p1select!="" && p2select==""){
                $(this).parent("li").toggleClass("p2select");
            }
        });      
    });
}

//fired when Youtube iframe API is ready
function onYouTubeIframeAPIReady() {
    //Jquery to get the first video
    $.ajax({
        url: apiUrl+"/videos/10"
    }).done(function(data){
        tabVideos=data;
        showTitleScreen("dog","cat");

        tabVideos.unshift({'video_id':'Qef16GuvaDU', 'animal1_id': 1, 'animal2_id': 2});
        
        //init (first load)
        player = new YT.Player('player', {
            height: '390',
            width: '640',
            videoId: 'Qef16GuvaDU',
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

        setTimeout(clearTitleScreen, 5000);
    });

}

// The API will call this function when the video player is ready.
function onPlayerReady(event) {
    var newWidth = $("div#containerPlayer").width();
    var newHeight = newWidth * 0.5625;
    player.setSize(newWidth, newHeight);

}

// Called to display the title at the end of the video
function onPlayerStateChange(event) {
    if (event.data == YT.PlayerState.ENDED)
        showEndScreen(p1select,p2select);
    else if (event.data == YT.PlayerState.PLAYING &&  $("div#endPlayer").css("display")=="block")
        clearEndScreen();
}


function init(){
    console.log("init");
    
    animalsInit();
    
    // This code loads the IFrame Player API code asynchronously.
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    
    initOK=true;
}

$(document).ready(function(){
    //init
    init();
    
    //tips
    $("a.tips").tipTip({
        defaultPosition: "top"
    });
 
    //PATH
    //Path.map("#!/home").to(init);
    Path.map("#!/video/:id/:p1/:p2").to(function(){
        //document.write("video = "+this.params['id']);
        /*if(! initOK)
            init();*/
        
        
    });
    //Path.root("#!/home");
    Path.listen();
});

$(window).resize(function(){
    var newWidth = $("div#containerPlayer").width();
    var newHeight = newWidth * 0.5625;
    player.setSize(newWidth, newHeight);
});
