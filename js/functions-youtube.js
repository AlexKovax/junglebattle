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
    $("div#titlePlayer span").html(p1.toUpperCase()+" V.S. "+p2.toUpperCase());
    $("div#titlePlayer").show();
    //Maj logos
    $("ul#animalsPlayer").html(
        "<li><a data-hover='"+getlogo(p1)+"' onclick='vote(1)' href='#'><img src='img/"+getlogo(p1)+"' /></a>"+
            "<span id='scoreP1'>"+p1.toUpperCase()+"</span></li>"+
        "<li><a data-hover='"+getlogo(p2)+"' onclick='vote(2)' href='#'><img src='img/"+getlogo(p2)+"' /></a>"+
            "<span id='scoreP2'>"+p2.toUpperCase()+"</span></li>"
    );
        
    $("ul#animalsPlayer li a img").hover(function(){
        $(this).attr("src","img/RONDplus1.png");
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
            //maj score
            console.log(data);
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