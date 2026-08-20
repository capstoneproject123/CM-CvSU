function showForm(formId) {
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
    document.getElementById(formId).classList.add("active");
}

$(document).ready(function(){
    //adding user concern form
    $(document).on("submit","#addform",function(e){
        e.preventDefault();
        //ajax
        $.ajax({
            url:"/Capstone%20-%20Copy/ajax.php",
            type:"POST",
            dataType:"json",
            data: new FormData(this),
            processData:false,
            contentType:false,
            beforeSend:function(){
                console.log("Waiting...Data is loading");
            },
            success:function(response){
                console.log(response);
            },
            error:function(request,error){
                console.log(arguments);
                console.log("Error"+ error);
            }
        });
    });
});
   