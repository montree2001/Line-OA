<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta name="description" content="ระบบประมวลผลสิ่งประดิษฐ์คนรุ่นใหม่ พัฒนาโดยวิทยาลัยการอาชีพปราสาท">
  <meta name="keywords" content="ระบบประมวลผล, สิ่งประดิษฐ์คนรุ่นใหม่, วิทยาลัยการอาชีพปราสาท">
  <meta property="og:image" content="https://ivt.prasat.ac.th/img/logo.png">
  <meta property="og:image:width" content="100"> <!-- ความกว้างของภาพ -->
  <meta property="og:image:height" content="100"> <!-- ความสูงของภาพ -->
  <meta property="og:title" content="ระบบประมวลผลสิ่งประดิษฐ์คนรุ่นใหม่ พัฒนาโดยวิทยาลัยการอาชีพปราสาท">
  <link rel="shortcut icon" type="image/png" href="img/logo.png" />

  <title>ระบบประมวลผลสิ่งประดิษฐ์คนรุ่นใหม่ พัฒนาโดยวิทยาลัยการอาชีพปราสาท</title>
  <?php include "struck/head.php"; ?>
</head>

<body>

  <div class="page-wrapper" id="main-wrapper">
    <!-- Sidebar Start -->


    <!--  Main wrapper -->
    <div class="body-wrapper">
      <?php include "head.php"; ?>

      <!-- ส่วนหัวข้อ -->


      <div class="container-fluid">
        <div class="card">
          <div class="card-body">
            <div class="container mt-12">

              <?php //เรียกข้อมูลประเภทสิ่งประดิษฐ์

              include 'conn.php';
              $sql = "SELECT * FROM `type` ORDER BY type_Name";
              $stmt = $pdo->prepare($sql);
              $stmt->execute();



              ?>

              <h5 class="text-center">ระบบประมวลผลสิ่งประดิษฐ์คนรุ่นใหม่</h5>
              <p class="text-center"> <i class="ti ti-copyright"></i> พัฒนาโดยวิทยาลัยการอาชีพปราสาท</p>
              <hr>
              <?php if ($stmt->rowCount() > 0) { ?>
                <h5 class="text-center"></i>ประกาศผลการลงคะแนน</h4>
                  <p class="text-center">กรุณาเลือกรายชื่อจากประเภทสิ่งประดิษฐ์ที่ต้องการ</p>


                  <div class="row">

                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
                      <div class="col-sm-12 col-xl-3">
                        <div class="card overflow-hidden rounded-2">
                          <div class="position-relative">
                            <a href="guarantee.php?type_id=<?php echo $row['type_id']; ?>"><img src="img/<?php echo $row['img']; ?>" class="card-img-top rounded-0" alt="..."></a>

                          </div>
                          <div class="card-body pt-3 p-4">
                            <h6 class="fw-semibold fs-4"><?php echo $row['type_Name'];  ?></h6>
                            <div class="d-flex align-items-center justify-content-between">



                            </div>
                          </div>
                        </div>
                      </div>
                    <?php }
                  } else { ?>
                    <div class="alert alert-warning text-center" role="alert">
                      ขออภัย! กรุณาสร้างประเภทสิ่งประดิษฐ์ ก่อนการดำเนินการ!
                    </div>
                  <?php } ?>



                  </div>



                  <!-- ส่วนเนื้อหา -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>





  <?php include 'struck/script.php'; ?>




  <?php
  // Include this function in your PHP file

  // Check if there's an alert in the session
  if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message']) && isset($_SESSION['alert_title'])) {
    // Display the alert using SweetAlert2
    echo "
        <script>
            Swal.fire({
                icon: '{$_SESSION['alert_type']}',
                title: '{$_SESSION['alert_title']}',
                text: '{$_SESSION['alert_message']}',
            });
        </script>
    ";
    // Clear the session variables to avoid displaying the same alert multiple times
    unset($_SESSION['alert_type']);
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_title']);
  }
  ?>
  <script>
    // =====================================
    var breakup = {
      color: "#adb5bd",
      series: [50, 50],
      labels: ["ลงคะแนน", "รอดำเนินการ"],
      chart: {
        width: 180,
        type: "donut",
        fontFamily: "Plus Jakarta Sans', sans-serif",
        foreColor: "#adb0bb",
      },
      plotOptions: {
        pie: {
          startAngle: 0,
          endAngle: 360,
          donut: {
            size: '75%',
          },
        },
      },
      stroke: {
        show: false,
      },

      dataLabels: {
        enabled: false,
      },

      legend: {
        show: false,
      },
      colors: ["#5D87FF", "#F9F9FD"],

      responsive: [{
        breakpoint: 991,
        options: {
          chart: {
            width: 150,
          },
        },
      }, ],
      tooltip: {
        theme: "dark",
        fillSeriesColor: false,
      },
    };

    var chart = new ApexCharts(document.querySelector("#ivention_chart"), breakup);
    chart.render();
  </script>
</body>

</html>