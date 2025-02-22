console.log("Script dosyası yüklendi");

// Language Proficiency Test namespace
var LPTTest = {
  init: function () {
    console.log("LPTTest initialized");
    jQuery(document).ready(function ($) {
      // Remove any existing onclick attributes to prevent conflicts
      $("#start-test-btn").removeAttr("onclick");
      $("#submit-test-btn").removeAttr("onclick");

      // Bind events with both click and touchstart
      const startBtn = document.getElementById("start-test-btn");
      if (startBtn) {
        startBtn.addEventListener("click", function (e) {
          e.preventDefault();
          LPTTest.startTest();
        });
        startBtn.addEventListener("touchstart", function (e) {
          e.preventDefault();
          LPTTest.startTest();
        });
      }

      const submitBtn = document.getElementById("submit-test-btn");
      if (submitBtn) {
        submitBtn.addEventListener("click", function (e) {
          e.preventDefault();
          LPTTest.submitTest();
        });
        submitBtn.addEventListener("touchstart", function (e) {
          e.preventDefault();
          LPTTest.submitTest();
        });
      }

      function updateProgress() {
        const totalQuestions = $(".lpt-question").length;
        const answeredQuestions = $('input[type="radio"]:checked').length;
        const progressPercentage = (answeredQuestions / totalQuestions) * 100;

        $(".progress-fill").css("width", progressPercentage + "%");

        if (answeredQuestions === totalQuestions) {
          $("#submit-test-btn").prop("disabled", false);
        }
      }

      // İlerleme çubuğunu ekle
      $("#lpt-test-questions").prepend(`
        <div class="test-progress">
          <div class="progress-bar">
            <div class="progress-fill"></div>
          </div>
        </div>
      `);

      // Seçenek seçildiğinde ilerlemeyi güncelle
      $(document).on("change", 'input[type="radio"]', function () {
        updateProgress();

        // Seçilen seçeneğin ebeveyn elementine sınıf ekle
        $(".option-label").removeClass("selected");
        $(this).closest(".option-label").addClass("selected");
      });

      // İlk yüklemede ilerlemeyi göster
      updateProgress();
    });
  },

  startTest: function () {
    console.log("Starting test...");
    var $ = jQuery;

    // Form değerlerini al
    var name = $("#student_name").val();
    var email = $("#student_email").val();
    var phone = $("#student_phone").val();
    var purpose = $("#learning_purpose").val();
    var kvkkApproved = $("#kvkk_approval").prop("checked");

    // Form doğrulama
    if (!name || !email || !phone) {
      alert("Lütfen tüm alanları doldurun.");
      return;
    }

    // Telefon numarası doğrulama
    if (!/^[0-9+\-\s()]{10,}$/.test(phone)) {
      alert("Lütfen geçerli bir telefon numarası girin.");
      return;
    }

    // Öğrenme amacı kontrolü
    if (!purpose) {
      alert("Lütfen dil öğrenme amacınızı seçin.");
      return;
    }

    // KVKK onayı kontrolü
    if (!kvkkApproved) {
      alert("Devam etmek için KVKK metnini onaylamanız gerekmektedir.");
      return;
    }

    // Öğrenci bilgilerini kaydet
    var studentInfo = {
      name: name.trim(),
      email: email.trim(),
      phone: phone.trim(),
      purpose: purpose,
    };

    try {
      console.log("Hiding student info form...");
      $("#student_info").val(JSON.stringify(studentInfo));

      // Önce mevcut display değerlerini kontrol et
      console.log("Current display values:");
      console.log(
        "Student info form display:",
        $("#lpt-student-info").css("display")
      );
      console.log(
        "Test questions display:",
        $("#lpt-test-questions").css("display")
      );

      // Display değerlerini doğrudan ayarla
      $("#lpt-student-info").css("display", "none");
      $("#lpt-test-questions").css("display", "block");

      // Kontrol için tekrar display değerlerini log'la
      console.log("After setting display values:");
      console.log(
        "Student info form display:",
        $("#lpt-student-info").css("display")
      );
      console.log(
        "Test questions display:",
        $("#lpt-test-questions").css("display")
      );

      // Alternatif yöntem olarak class'ları da güncelle
      $("#lpt-student-info").removeClass("active");
      $("#lpt-test-questions").addClass("active");

      console.log("Test started for:", studentInfo);

      // Scroll to top
      window.scrollTo(0, 0);
    } catch (error) {
      console.error("Error starting test:", error);
    }
  },

  submitTest: function () {
    console.log("Submitting test...");
    var $ = jQuery;

    // Disable submit button
    var $submitBtn = $("#submit-test-btn");
    $submitBtn.prop("disabled", true).text("Gönderiliyor...");

    // Collect answers
    var answers = {};
    var allAnswered = true;

    $(".lpt-question").each(function () {
      var questionId = $(this).data("question-id");
      var $checked = $('input[name="question_' + questionId + '"]:checked');

      if ($checked.length === 0) {
        allAnswered = false;
        return false; // break the loop
      }

      answers[questionId] = $checked.val();
    });

    if (!allAnswered) {
      alert("Lütfen tüm soruları cevaplayın.");
      $submitBtn.prop("disabled", false).text("Testi Gönder");
      return;
    }

    // Prepare form data
    var formData = new FormData();
    formData.append("action", "submit_language_test");
    formData.append("nonce", lptAjax.nonce);
    formData.append("answers", JSON.stringify(answers));
    formData.append("language", $(".lpt-test-container").attr("data-language"));
    formData.append("student_info", $("#student_info").val());

    console.log("Sending test data:", {
      answers: answers,
      language: $(".lpt-test-container").attr("data-language"),
      student_info: $("#student_info").val(),
    });

    // Send AJAX request
    $.ajax({
      url: lptAjax.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log("Server response:", response);

        if (response.success) {
          $("#lpt-test-questions").hide();
          var $resultDiv = $("#lpt-result");
          $resultDiv.html(
            '<div class="lpt-result-message">' +
              response.data.message +
              "</div>"
          );
          $resultDiv.show();
        } else {
          alert(response.data || "Bir hata oluştu. Lütfen tekrar deneyin.");
          $submitBtn.prop("disabled", false).text("Testi Gönder");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error:", {
          status: status,
          error: error,
          response: xhr.responseText,
        });
        alert("Bir hata oluştu. Lütfen tekrar deneyin.");
        $submitBtn.prop("disabled", false).text("Testi Gönder");
      },
    });
  },
};

// Initialize the test system
LPTTest.init();
