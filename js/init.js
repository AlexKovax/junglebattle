//////////////
// init.js //
////////////

/////////////////////////
//VAR
var player,
    playlist,
    tabAnimals,
    tabVideos;

var voted = false;
var initDone = false;
var isPlaying = false;

var p1select="";
var p2select="";
var nvideos=10;
var currentVideo = 0;
var apiUrl="http://junglebattle.com/api";

var urlp1="";
var urlp2="";
var urlvideo="";

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
            $("li").removeClass("p1selectTmp p2selectTmp");
            
            if(p1select==="" || (p1select!="" && p2select!="")){
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
                    loadVideoByNumber(0);
                });
            }else if(p1select!="" && p2select==""){
                p2select=$(this).data("animal");
                $(this).parent("li").addClass("p2select");
                $.ajax({
                    url: "http://junglebattle.com/api/videos/"+p2select+"/"+p1select+"/"+nvideos
                }).done(function(data){
                    tabVideos=data;
                    loadVideoByNumber(0);
                });
            }
            e.preventDefault();
        });
    
        //HOVER
        $("a.animalPlayer").hover(function(){
            if(p1select=="" || (p1select!="" && p2select!="")){
                $(this).parent("li").addClass("p1selectTmp");
            }else if(p1select!="" && p2select==""){
                $(this).parent("li").addClass("p2selectTmp");
            }
        },function(){
            if(p1select=="" || (p1select!="" && p2select!="")){
                $(this).parent("li").removeClass("p1selectTmp");
            }else if(p1select!="" && p2select==""){
                $(this).parent("li").removeClass("p2selectTmp");
            }
        });
    });
}

//fired when Youtube iframe API is ready
function onYouTubeIframeAPIReady() {
    //Jquery to get the first video
    var args="";
    if(urlp1!="")
        args="/"+urlp1;
    if(urlp2!="")
        args+="/"+urlp2;
    
    $.ajax({
        url: apiUrl+"/videos"+args+"/10"
    }).done(function(data){
        tabVideos=data;
        if(urlvideo!="" && urlp1!="" && urlp2!="")
            tabVideos.unshift({'video_id':urlvideo, 'animal1': urlp1, 'animal2': urlp2});
        loadPlayer();//will trigger the play in onPlayerReady function
    });
}

// The API will call this function when the video player is ready.
function onPlayerReady(event) {
    loadVideoByNumber(0);
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
    animalsInit();
    
    // This code loads the IFrame Player API code asynchronously.
    var tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    var firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    
    initDone=true;
}

//////////////////////
// READY
$(document).ready(function(){    
    //tips
    $("a.tips").tipTip({
        defaultPosition: "top"
    });
 
    //PATH
    Path.map("#!/home").to(init);
    
    Path.map("#!/video/:id/:p1/:p2").to(function(){
        urlp1=this.params['p1'];
        urlp2=this.params['p2'];
        urlvideo=this.params['id'];
        if(! initDone)
            init();
        else
            loadVideoFromUrl(this.params['p1'],this.params['p2'],this.params['id']);        
    });
    
    Path.root("#!/home");
    Path.listen();
});

$(window).resize(function(){
    var newWidth = $("div#containerPlayer").width();
    var newHeight = newWidth * 0.5625;
    player.setSize(newWidth, newHeight);
});
