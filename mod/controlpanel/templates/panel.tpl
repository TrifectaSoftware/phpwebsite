<style type="text/css">

/*------------- CONTROL PANEL ------------------*/

/*--------------- Tabs ---------------*/
#tabmenu {
  width : 100%;
  padding : 3px;
margin-bottom : -2px;
margin-left : -2px;

}

#tabmenu li {
  display: inline;
  overflow: hidden;
  list-style-type: none;
  border: 2px solid #7B95B4;
  font-weight: bold;
}


#tabmenu li.active {
  color : white;
  background-color : #92B4DE;
  font-size: 0.8em;
  padding: 5px 5px 4px 5px;
  border-bottom : none;
}

#tabmenu li.inactive {
  background-color : #CBDFEB;
  font-size: 0.8em;
  padding: 5px 5px 2px 5px;
}

#tabmenu li.active a:link { 
	text-decoration: none; 
	color: #3A5574;
}

#tabmenu li.active a:visited { 
	text-decoration: none; 
	color: #2D3D55;
}

#tabmenu li.active a:hover { 
	text-decoration: none; 
	color: white;
}

#tabmenu li.inactive a:link { 
	text-decoration: none; 
	color: #3A5574;
}

#tabmenu li.inactive a:visited { 
	text-decoration: none; 
	color: #2D3D55;
}

#tabmenu li.inactive a:hover { 
	text-decoration: none; 
	color: white;
}

#tabmenu li.inactive:hover {
  background: #9FC5EF;
  color: white;
}


/*-------------- Links -----------------*/
#frame {
  margin-left : 1px;
  margin-top : -2px;
  display : table;
  width : 100%;
  background: #92B4DE;
  height: auto;
  padding: 5px;
  border: 2px solid #7B95B4;
}

#backgrd {
  background-color : white;
  padding : 5px;
  padding-left : 0px;
  padding-right : 0px;
  border-left: 2px solid #7B95B4;
  border-top: 2px solid #7B95B4;
  height : 100%;
  width : 100%;
}


div.link {
font-size : .8em;
margin : 5px;
padding : 5px;
float : left;
position : relative;
width : 150px;
//min-height : 100px;
text-align : top;
}

div.link img {
float : left;
margin : 5px;
margin-bottom : 20%;
}

div.link a{
 font-size : .9em;
 font-weight : bold;
}
</style>

<ul id="tabmenu">{TABS}</ul>
<div id="frame">
  <div id="backgrd">
    {LINKS}
  </div>
</div>
