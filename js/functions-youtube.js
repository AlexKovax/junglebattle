////////////////////////
// functions youtube //
//////////////////////

function loadVideo(p1,p2,video_id){        
    showTitleScreen(p1,p2);
    player.loadVideoById(video_id, 0, "large");
    setTimeout(clearTitleScreen, 3000);
}

function loadNextVideo(){
    voted=false;
    currentVideo++;
    
    //TODO: Test quand on arrive à la fin du tableau
    
    //TODO: Test 2 si le mec fais next 50 fois de suite -> find a girlfriend
    
    c=tabVideos[currentVideo];
    loadVideo(c.animal1,c.animal2,c.video_id);
}

function stopVideo() {
    player.stopVideo();
}

function showTitleScreen(p1,p2){
    //Maj surimp
    $("div#titlePlayer").html(p1+" VS "+p2);
    $("div#titlePlayer").show();
    //Maj logos
    $("ul#animalsPlayer").html(
        "<li><a data-hover='"+getlogo(p1)+"' onclick='vote(1)' href='#'><img src='img/"+getlogo(p1)+"' /></a></li>"+
        "<li><a data-hover='"+getlogo(p2)+"' onclick='vote(2)' href='#'><img src='img/"+getlogo(p2)+"' /></a></li>"
    );
        
    $("ul#animalsPlayer li a img").hover(function(){
        $(this).attr("src","img/RONDVERT.png");
    },function(){
        $(this).attr("src","img/"+$(this).parent("a").data("hover"));
    });
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

function vote(id){
    if(! voted){
        $("ul#animalsPlayer li a img").unbind('mouseenter');

        $.ajax({
            url: "http://junglebattle.com/api/vote/"+tabVideos[currentVideo].video_id+"/"+id
        }).done(function(data){
            voted=true;
            //traitement graphique
            //winner
            if(data[0].num_votes > data[1].num_votes)
                $("ul#animalsPlayer li:first").addClass("winner");
            else
                $("ul#animalsPlayer li:nth-child(2)").addClass("winner");
            //player select
            if(id == 1)
                $("ul#animalsPlayer li:first").addClass("select");
            else
                $("ul#animalsPlayer li:nth-child(2)").addClass("select");        
        });
    }
}