<div class="col-xxl-4 col-md-6" data-bs-toggle="modal" data-bs-target="#basicModal"
    data-campus="<?php echo htmlspecialchars($row['campus']); ?>"
    data-college="<?php echo htmlspecialchars($row['college']); ?>"
    data-names="<?php echo htmlspecialchars($row['names']); ?>"
    data-school="<?php echo htmlspecialchars($row['school']); ?>"
    data-program="<?php echo htmlspecialchars($row['program']); ?>"
    data-yearofstudy="<?php echo htmlspecialchars($row['yearofstudy']); ?>"
    data-expireddate="<?php echo htmlspecialchars($row['expireddate']); ?>"
    data-regnumber="<?php echo htmlspecialchars($row['regnumber']); ?>"
    data-picture="../Students/<?php echo $row['picture']; ?>">
    <div class="card info-card"
        style="background: url('./lox.png') no-repeat center center/cover; background-size: cover; position: relative;border:1px solid gray">
        <div class="card-body">
            <div class="ps-1" style="color:black">
                <div class="row" style="padding-top:0.3cm;">
                    <div class="col-5" style="">
                        <img src="./ur-logo.png" alt="Logo" style="height:2.3cm;width:7cm;float:left;">
                    </div>

                    <div class="col-7">
                        <h6
                            style="padding-top:0.6cm;text-transform:uppercase;text-align:right;font-size:28px;color:black; font-family: Arial, Helvetica, sans-serif;margin-right:0.5cm">
                            <b>
                                <?php echo htmlspecialchars($row['campus']); ?> Campus
                        </h6>
                        </b>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                    <h4
    style="text-align:center;text-transform:uppercase;font-weight:bold;font: size 16px;font-family: Arial Narrow">
    <b><?php 
    if (htmlspecialchars($row['college']) == "CASS") {
        ?>
            COLLEGE OF ARTS AND SOCIAL SCIENCES
        <?php
    }
    if (htmlspecialchars($row['college']) == "CBE") {
        ?>
            COLLEGE OF BUSINESS AND ECONOMICS
        <?php
    }
    if (htmlspecialchars($row['college']) == "CST") {
        ?>
            COLLEGE OF SCIENCE AND TECHNOLOGY
        <?php
    }
    if (htmlspecialchars($row['college']) == "CAVEM") {
        ?>
            COLLEGE OF AGRICULTURE, ANIMAL SCIENCES, AND VETERINARY MEDICINE
        <?php
    }
    if (htmlspecialchars($row['college']) == "CMHS") {
        ?>
            COLLEGE OF MEDICINE AND HEALTH SCIENCES
        <?php
    }
    if (htmlspecialchars($row['college']) == "CE") {
        ?>
            COLLEGE OF EDUCATION
        <?php
    }
    ?></b>
</h4>

                    </div>
                </div>

                <div class="row">
                    <div class="col-9">

                        <h5
                            style="text-align:;padding-bottom:0.2cm;font-size:20px;font-family: Arial;color:black;margin-top:-0.2cm;margin-left:5cm">
                            <b class="formatted-underline">STUDENT ID CARD</b>

                        </h5>
                        <br>

                        <div>
                            <h6 style="font-size:20px;color:black; font-family: Arial, Helvetica, sans-serif;">
                                <b>
                                    <div style="display:;">
                                        <div class="title" style="flex-shrink: 0;">
                                            <span style='color:;text-transform:uppercase;'>NAMES: </span>
                                            <?php echo htmlspecialchars($row['names']); ?>
                                        </div>

                                    </div>
                                </b>
                            </h6>
                        </div>

                        <div style="margin-top:0.2cm">
                            <h6
                                style="text-transform:uppercase;font-size:20px;color:black; font-family: Arial, Helvetica, sans-serif;">
                                <b>
                                    <div style="display:;">
                                        <div class="title" style="flex-shrink: 0;">
                                            <span style='color:'>SCHOOL: </span>
                                            <?php echo htmlspecialchars($row['school']); ?>
                                        </div>

                                    </div>
                                </b>
                            </h6>
                        </div>

                        <div style="margin-top:0.2cm">
                            <h6
                                style="text-transform:uppercase;font-size:20px;color:black; font-family: Arial, Helvetica, sans-serif;">
                                <b>
                                    <div style="display: ;">
                                        <div class="title" style="flex-shrink: 0;">
                                            <span style='color:'>PROGRAM: </span>
                                            <?php echo htmlspecialchars($row['program']); ?>
                                        </div>

                                    </div>
                                </b>
                            </h6>
                        </div>

                        <div style="margin-top:0.2cm">
                            <h6
                                style="text-transform:uppercase;font-size:20px;color:black; font-family: Arial, Helvetica, sans-serif;">
                                <b>
                                    <div style="display: flex;">
                                        <div class="title" style="flex-shrink: 0;">
                                            <span style='color:'>Year of Study: </span>
                                        </div>
                                        <div class="content" style="margin-left: 0cm">
                                            <?php echo htmlspecialchars($row['yearofstudy']); ?>
                                        </div>
                                    </div>
                                </b>
                            </h6>
                        </div>





                        <div style="margin-top:0.9cm">
                            <h6
                                style="text-transform:uppercase;font-size:20px;color:black; font-family: Arial, Helvetica, sans-serif;">
                                <b>
                                    <div style="display: flex;">
                                        <div class="title" style="flex-shrink: 0;">
                                            Expiry date: </div>
                                        <div class="content" style="margin-left: 0cm">

                                            <?php echo $exp; ?>
                                        </div>
                                    </div>
                                </b>
                            </h6>
                        </div>





                    </div>
                    <div class="col-3" style="margin-left:-0.3cm">
                        <?php
                        if (!$row['picture'] == null) {
                            ?><br>
                            <img src="../Students/<?php echo $row['picture']; ?>" alt="" style="width:3.7cm;margin-top:1cm">
                            <p>
                                <b
                                    style="text-transform:uppercase;font-size:25px;color:black; font-family: Arial, Helvetica, sans-serif;text-align:center">
                                    <!-- Reg number -->
                                    <center> <?php echo htmlspecialchars($row['regnumber']); ?></center>
                                </b>
                            </p>
                            <?php
                        } else {
                            ?>
                            <br>
                            <div alt="" style="height:3cm;width:3cm;margin-top:0.3cm;border:1px solid black">
                            </div>
                            <p style="margin-top:0.3cm">
                                <b><i>Reg:
                                        <?php echo htmlspecialchars($row['regnumber']); ?></i></b>
                            </p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>