        <!--form modal-->
            <div class="modal fade" id="usermodal" role="dialog">
                <div class="modal-dialog">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fs-5" id="exampleModalLabel">Adding Complaint/ Inquiry</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form id="addform" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">



                        <!--Concern Type-->
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                        <label class="btn btn-secondary active">
                            <input type="radio" name="concern_type" id="complaint" value="Complaint" autocomplete="off" checked> Complaint
                        </label>
                        <label class="btn btn-secondary">
                            <input type="radio" name="concern_type" id="inquiry" value="Inquiry" autocomplete="off"> Inquiry
                        </label>
                        </div>         



                        <!--Title-->
                        <div class="form-group">
                            <label >Title</label>                            
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-dark"><i class="fa-solid fa-user text-light"></i></span>
                                </div>
                                <input type="text" class="form-control" placeholder="Enter Title..." autocomplete="off" required="required" id="title" name="title">
                            </div>
                        </div>



                        <!--Complaint/ Inquiry Category type-->
                        <div class="form-group">
                            <label >Category Type</label>                            
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-dark"><i class="fa-solid fa-layer-group text-light"></i></span>
                                </div>
                                <select name="category" required>
                                    <option value="">--Select Category--</option>
                                    <option value="Academic Concerns">Academic Concerns</option>
                                    <option value="Technical Issues">Technical Issues</option>
                                    <option value="Administrative">Administrative</option>
                                    <option value="Faculty">Faculty</option>
                                    <option value="Facilities & Equipment">Facilities & Equipment</option>
                                </select>

                            </div>
                        </div>




                        <!--Priority Level-->
                        <div class="orm-group">
                            <label >Priority</label>                            
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-dark"><i class="fa-solid fa-circle-exclamation text-light"></i></span>
                                </div>
                                <select name="priority" required>
                                    <option value="">--Select Priority Level--</option>
                                    <option value="Low - Can wait">Low - Can wait</option>
                                    <option value="Medium - Normal timeline">Medium - Normal timeline</option>
                                    <option value="High - Urgent">High - Urgent</option>
                                    
                                </select>
                            </div>
                        </div>                       





                        <!--Description-->
                        <div class="form-group">
                            <label >Description</label>                            
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-dark"><i class="fa-solid fa-t text-light"></i></span>
                                </div>
                                <div class="form-floating">
                                    <textarea class="form-control" placeholder="Leave a description here" name="description" id="description" style="height: 100px"></textarea>
                                    <label for="description">Please describe in more details...</label>
                                </div>
                            </div>
                        </div>





                        <!--File-->
                        <div class="form-group">
                            <label >Evidences</label>                            
                            <div class="input-group">
                                <label class="custom-file-label" for="user_evidence">Choose File(s)</label>
                                <input type="file" class="custom-file-input" name="evidence" id="user_evidence">
                            </div>
                        </div>
                    </div>


                    <div class="modal-footer">
                         <button type="submit" class="btn btn-dark">Submit</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <!--2 input fileds first for adding and next for updating, deleting, or viewing concerns-->
                        <input type="hidden" name="action" value="addUserconcern">
                        <input type="hidden" name="userFormId" value="">
                    </div>
                    </form>
                    </div>
                </div>
            </div>