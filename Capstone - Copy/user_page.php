<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Page</title>
    <link rel="stylesheet" href="style.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.0/css/all.min.css" integrity="sha512-ApSLB1Pd3/bZN8fWB/RG9YhN/7bd9Hkf3AGaE2mPfebjrxagjuBtx2GcgdqIlJkUzwylBo61r9Xa9NmgBI0swA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body style="background: #fff;">
    <div class="hero">
        <?php include 'navbar.php'; ?> 





        <div class="container">
        <h1>Track your Complaints and Inquiry</h1>


        
        <!--form modal-->
        <?php include 'form.php'?>
        <?php include 'view.php'?>
            
            <!--input search and button aection-->
            <div class="row mb-3">
                <div class="col-10">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-dark"><i class="fa-solid fa-magnifying-glass text-light"></i></span>
                        </div>
                        <input type="text" class="form-control" placeholder="Search user...">
                    </div>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-dark" data-toggle="modal" data-target="#usermodal">
                    Add new Complaint/Inquiry
                    </button>
                </div>
        </div>


<!--table-->
<?php include 'tableData.php'?>


        <!--pagination-->
        <nav aria-label="Page navigation example" id="pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
            </ul>
        </nav>
    </div>
    
    
    <script>
        let subMenu = document.getElementById("subMenu");

        function toggleMenu(){
            subMenu.classList.toggle("open-menu");
        }
    </script>
    <!-- jQuery FIRST -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

<!-- Popper.js SECOND -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js"></script>

<!-- Bootstrap JS THIRD -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js"></script>

<!--js file-->
    <script src="js/script.js"></script>
</body>
</html>