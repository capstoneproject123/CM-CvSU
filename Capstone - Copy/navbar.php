<style>
nav{
    
}

nav ul {
    width: 100%;
    text-align: right;
}

nav ul li {
    display: inline-block;
    list-style: none;
    
}

nav ul li a {
    color: #fff;    
    text-decoration: none;
}

.logo{
    width: 120px;
}

.user-pic{
    width: 40px;
    cursor: pointer;
    margin-left: 30px;
    border-radius: 50%;
}

.sub-menu-wrap{
    position: absolute;
    top: 100%;
    right: 10%;
    width: 320px;
    max-height: 0px;
    overflow: hidden;
    transition: max-height 0.5s;
}

.sub-menu-wrap.open-menu{
    max-height: 400px;
}

.sub-menu{
    background: white;
    padding: 20px;
    margin: 10px;
}

.user-info{
    display: flex;
    text-align: center;
}

.user-info h2{
    font-weight: 500;
}

.user-info img{
    width: 60px;
    border-radius: 50%;
    margin-right: 15px;
}

.sub-menu hr{
    border: 0;
    height: 1px;
    width: 100%;
    background: #ccc;
    margin: 15px 0 10px;
}

.hero {
    width: 100%;
    min-height: 100vh;
    background-color: #eceaff;
    color: 525252;
}

.sub-menu-link{
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #525252;
    margin: 12px 0;
    
}
</style>
<nav style="background: #1B651B; width: 100%; padding: 10px 10%; display: flex; align-items: center; justify-content: space-between; position: relative;">
            <img src="logo.png" class="logo">
            <h1>LOGO</h1>
            <h3>logo title</h3>
            <ul>
                <li style="margin: 10px 20px;"><a href="#" style="margin: 10px 20px;">Home</a></li>
            </ul>
            <img src="user.png" class="user-pic" onclick="toggleMenu()">
        

            <div class="sub-menu-wrap" id="subMenu">
                <div class="sub-menu">
                    <div class="user-info">
                        <img src="user.png">
                        <h2><span><?= $_SESSION['name']; ?></span></h2>
                    </div>
                    <hr>
                    <a href="#" class="sub-menu-link">
                        <button onclick="window.location.href='logout.php'">Logout</button>
                    </a>
                </div>
            </div>

</nav>