console.log("Script dosyası yüklendi");

// Language Proficiency Test namespace
var LPTTest = {
  init: function () {
    console.log("LPTTest initialized");
    jQuery(document).ready(function ($) {
      // Hide the result div initially
      $("#lpt-result").hide();

      // Adım adım gösterme için, başlangıçta test sorularını gizle
      $("#lpt-test-questions").hide();

      // İlerleme çubuğunu gizle, sadece adım adım gösterme başladığında görünecek
      $(".test-progress").hide();

      // DOM elementlerini doğru şekilde tanıla
      console.log("Initial DOM Check:");
      console.log("start-test-btn exists:", $("#start-test-btn").length);
      console.log("submit-test-btn exists:", $("#submit-test-btn").length);

      // Remove any existing onclick attributes to prevent conflicts
      $("#start-test-btn").removeAttr("onclick");
      $("#submit-test-btn").removeAttr("onclick");

      // Doğrudan jQuery olay bağlayıcı kullanarak butonları aktif et
      $("#start-test-btn").on("click", function (e) {
        e.preventDefault();
        console.log("Start test button clicked via jQuery handler");
        LPTTest.startTest();
      });

      $("#submit-test-btn").on("click", function (e) {
        e.preventDefault();
        console.log("Submit test button clicked via jQuery handler");
        LPTTest.submitTest();
      });

      // EventListener metodunu yedek olarak da ekle
      const startBtn = document.getElementById("start-test-btn");
      if (startBtn) {
        startBtn.addEventListener("click", function (e) {
          e.preventDefault();
          console.log("Start test button clicked via addEventListener");
          LPTTest.startTest();
        });
        startBtn.addEventListener("touchstart", function (e) {
          e.preventDefault();
          console.log("Start test button touched");
          LPTTest.startTest();
        });
      } else {
        console.error("Start button not found in DOM!");
      }

      function updateProgress() {
        // Adım adım gösterme modunda çalışmıyorsa, eski ilerleme mantığını kullan
        if ($("#lpt-test-questions .lpt-question:hidden").length === 0) {
          const totalQuestions = $(".lpt-question").length;
          const answeredQuestions = $('input[type="radio"]:checked').length;
          const progressPercentage = (answeredQuestions / totalQuestions) * 100;

          $(".progress-fill").css("width", progressPercentage + "%");

          // Update progress text
          $(".progress-text").text(
            answeredQuestions +
              " / " +
              totalQuestions +
              " soru cevaplandı (" +
              Math.round(progressPercentage) +
              "%)"
          );

          if (answeredQuestions === totalQuestions) {
            $("#submit-test-btn").prop("disabled", false).css({
              opacity: "1",
              "background-color": "#3498db",
              cursor: "pointer",
            });
          } else {
            $("#submit-test-btn").prop("disabled", true).css({
              opacity: "0.7",
              "background-color": "#94a3b8",
              cursor: "not-allowed",
            });
          }
        }
      }

      // İlerleme çubuğu HTML yapısı artık PHP tarafında eklenmiştir
      /* 
      $("#lpt-test-questions").prepend(`
        <div class="test-progress">
          <div class="progress-bar">
            <div class="progress-fill"></div>
          </div>
        </div>
      `);
      */

      // Seçenek seçildiğinde ilerlemeyi güncelle ve stil değişikliklerini uygula
      $(document).on("change", 'input[type="radio"]', function () {
        // Tüm seçeneklerin stilini sıfırla
        $(".option-label").css({
          background: "#f8fafc",
          border: "1px solid #edf2f7",
          transform: "none",
        });

        // Seçilen seçeneğin stilini güncelle
        $(this).closest(".option-label").css({
          background: "#ebf5ff",
          border: "1px solid #93c5fd",
          transform: "translateX(5px)",
        });

        // Seçilen radio butonun stilini güncelle
        $(this).css({
          "border-color": "#2563eb",
          background: "#2563eb",
        });

        // Radio buton içine beyaz nokta ekle
        $(this).after(
          "<span style=\"content: ''; position: absolute; width: 6px; height: 6px; background: white; border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%);\"></span>"
        );

        // İlerlemeyi güncelle
        updateProgress();
      });

      // Sayfadaki mevcut seçilmiş radio butonları için de stil uygula
      $('input[type="radio"]:checked').each(function () {
        $(this).closest(".option-label").css({
          background: "#ebf5ff",
          border: "1px solid #93c5fd",
          transform: "translateX(5px)",
        });

        $(this).css({
          "border-color": "#2563eb",
          background: "#2563eb",
        });
      });

      // KVKK checkbox özel stilleri
      $("#kvkk_approval").on("change", function () {
        if ($(this).is(":checked")) {
          $(this).css({
            "border-color": "#2563eb",
            "background-color": "#2563eb",
          });

          // Tik işareti ekle
          if (!$(this).next(".checkmark").length) {
            $(this).after(
              '<span class="checkmark" style="content: \'\'; position: absolute; top: 3px; left: 6px; width: 5px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);"></span>'
            );
          }
        } else {
          $(this).css({
            "border-color": "#cbd5e1",
            "background-color": "transparent",
          });

          // Tik işaretini kaldır
          $(this).next(".checkmark").remove();
        }
      });

      // İlk yüklemede ilerlemeyi göster
      updateProgress();

      // Form input ve select elementlerine focus efektleri
      $(".lpt-form input, .lpt-form select")
        .on("focus", function () {
          $(this).css({
            "border-color": "#ffc904",
            "box-shadow": "0 0 0 3px rgba(255, 201, 4, 0.1)",
            "background-color": "#ffffff",
          });
        })
        .on("blur", function () {
          $(this).css({
            "border-color": "#e2e8f0",
            "box-shadow": "none",
            "background-color": "#ffffff",
          });
        });

      // Seçenek hover efektleri
      $(".option-label").hover(
        function () {
          $(this).css({
            transform: "translateX(8px)",
            "border-color": "#ffc904",
            background: "rgba(255, 201, 4, 0.05)",
          });
          $(this).find(".option-letter").css("color", "#ffc904");
        },
        function () {
          if (!$(this).find("input[type='radio']").is(":checked")) {
            $(this).css({
              transform: "translateX(0)",
              "border-color": "#e2e8f0",
              background: "#f8fafc",
            });
            $(this).find(".option-letter").css("color", "#64748b");
          }
        }
      );

      // Radio button seçim efektleri
      $(".option-label input[type='radio']").on("change", function () {
        // Tüm seçenekleri resetle
        $(this)
          .closest(".lpt-question")
          .find(".option-label")
          .css({
            transform: "translateX(0)",
            "border-color": "#e2e8f0",
            background: "#f8fafc",
          })
          .find(".option-letter")
          .css("color", "#64748b");

        // Seçili seçeneği güncelle
        if ($(this).is(":checked")) {
          $(this)
            .closest(".option-label")
            .css({
              transform: "translateX(8px)",
              "border-color": "#ffc904",
              background: "rgba(255, 201, 4, 0.05)",
            })
            .find(".option-letter")
            .css("color", "#ffc904");

          // Radio button stilini güncelle
          $(this).css({
            "border-color": "#ffc904",
            background: "#ffc904",
          });

          // Checkmark ekle
          if (!$(this).next(".radio-checkmark").length) {
            $(this).after(
              '<span class="radio-checkmark" style="content: \'\'; position: absolute; top: 50%; left: 50%; width: 8px; height: 8px; background: white; border-radius: 50%; transform: translate(-50%, -50%);"></span>'
            );
          }
        }
      });

      // KVKK checkbox özel stilleri
      $("#kvkk_approval").on("change", function () {
        if ($(this).is(":checked")) {
          $(this).css({
            "border-color": "#ffc904",
            "background-color": "#ffc904",
          });

          // Tik işareti ekle
          if (!$(this).next(".checkmark").length) {
            $(this).after(
              '<span class="checkmark" style="content: \'\'; position: absolute; top: 6px; left: 7px; width: 6px; height: 10px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);"></span>'
            );
          }
        } else {
          $(this).css({
            "border-color": "#cbd5e1",
            "background-color": "transparent",
          });

          // Tik işaretini kaldır
          $(this).next(".checkmark").remove();
        }
      });

      // Buton hover efektleri
      $(".button-primary").hover(
        function () {
          $(this).css({
            transform: "translateY(-2px)",
            "box-shadow": "0 12px 24px rgba(255, 201, 4, 0.25)",
          });
        },
        function () {
          $(this).css({
            transform: "translateY(0)",
            "box-shadow": "0 8px 16px rgba(255, 201, 4, 0.2)",
          });
        }
      );
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

    console.log("Form values:", { name, email, phone, purpose, kvkkApproved });

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
      console.log("Radical approach to hide form and show questions");

      // Öğrenci bilgilerini hidden input'a kaydet
      $("#student_info").val(JSON.stringify(studentInfo));

      // DOM elementlerini kontrol et
      var studentInfoDiv = document.getElementById("lpt-student-info");
      var testQuestionsDiv = document.getElementById("lpt-test-questions");
      var progressBar = document.querySelector(".test-progress");

      console.log("Element references:", {
        studentInfoDiv: studentInfoDiv,
        testQuestionsDiv: testQuestionsDiv,
        progressBar: progressBar,
      });

      // Direk DOM manipülasyonu ile gizle/göster
      if (studentInfoDiv) {
        // Birkaç yöntemi birden deneyelim
        studentInfoDiv.style.cssText =
          "display: none !important; visibility: hidden !important; opacity: 0 !important; height: 0 !important; overflow: hidden !important;";
        // Alternatif olarak element'i tamamen kaldıralım
        // studentInfoDiv.parentNode.removeChild(studentInfoDiv);
      }

      if (testQuestionsDiv) {
        // Çeşitli stil özellikleriyle görünür yap
        testQuestionsDiv.style.cssText =
          "display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;";
      }

      if (progressBar) {
        progressBar.style.cssText =
          "display: block !important; visibility: visible !important;";
      }

      // Yeni CSS kuralları ekleyerek zorla gizle/göster
      var styleEl = document.createElement("style");
      styleEl.type = "text/css";
      styleEl.innerHTML = `
        #lpt-student-info { display: none !important; visibility: hidden !important; opacity: 0 !important; }
        #lpt-test-questions { display: block !important; visibility: visible !important; opacity: 1 !important; }
        .test-progress { display: block !important; visibility: visible !important; }
      `;
      document.head.appendChild(styleEl);

      console.log("Direct DOM manipulation complete");

      // Sayfayı yukarı kaydır
      window.scrollTo(0, 0);

      // Adım adım gösterme işlevini başlat
      setTimeout(function () {
        console.log("Starting step-by-step display with delay");
        LPTTest.showQuestionsStepByStep();
      }, 100);

      console.log("Test started for:", studentInfo);
    } catch (error) {
      console.error("Error starting test:", error);
      alert("Sınav başlatılırken bir hata oluştu: " + error.message);
    }
  },

  submitTest: function () {
    console.log("Submitting test with radical new approach...");

    // Immediately show loading state
    var submitBtn =
      document.querySelector(".form-actions button:enabled") ||
      document.getElementById("last-submit-btn");
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<span style="display: inline-flex; align-items: center;"><span style="display: inline-block; width: 16px; height: 16px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; margin-right: 8px; animation: spin 1s linear infinite;"><!----></span> Gönderiliyor...</span>';
    }

    // Add spinner animation style
    if (!document.getElementById("spinner-style")) {
      var styleEl = document.createElement("style");
      styleEl.id = "spinner-style";
      styleEl.textContent =
        "@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }";
      document.head.appendChild(styleEl);
    }

    // Collect all answers
    var answers = {};
    var allAnswered = true;
    var unansweredQuestions = [];

    var questions = document.querySelectorAll(".lpt-question");
    questions.forEach(function (question) {
      var questionId = question.getAttribute("data-question-id");
      var questionNumber =
        question
          .querySelector(".question-number")
          ?.textContent.replace(".", "") || "?";
      var checkedInput = question.querySelector('input[type="radio"]:checked');

      if (!checkedInput) {
        allAnswered = false;
        unansweredQuestions.push(questionNumber);
        question.style.border = "1px solid #e74c3c";
        question.style.boxShadow = "0 0 8px rgba(231, 76, 60, 0.2)";
      } else {
        question.style.border = "1px solid #e5e5e5";
        question.style.boxShadow = "0 4px 6px rgba(0, 0, 0, 0.05)";
        answers[questionId] = checkedInput.value;
      }
    });

    if (!allAnswered) {
      alert(
        "Lütfen tüm soruları cevaplayın.\nCevaplanmamış soru(lar): " +
          unansweredQuestions.join(", ")
      );
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Testi Tamamla";
      }
      return;
    }

    // Prepare data for submission
    var formData = new FormData();
    formData.append("action", "submit_language_test");
    formData.append("nonce", lptAjax.nonce);
    formData.append("answers", JSON.stringify(answers));

    var testContainer = document.querySelector(".lpt-test-container");
    if (testContainer) {
      formData.append(
        "language",
        testContainer.getAttribute("data-language") || ""
      );
    }

    var studentInfoInput = document.getElementById("student_info");
    if (studentInfoInput) {
      formData.append("student_info", studentInfoInput.value || "{}");
    }

    console.log("Sending test data:", {
      answers: answers,
      language: testContainer
        ? testContainer.getAttribute("data-language")
        : "",
      student_info: studentInfoInput ? studentInfoInput.value : "{}",
    });

    // ULTRA FAILSAFE: Show results after a delay no matter what
    var ultraFailsafeTimeout = setTimeout(function () {
      console.log("ULTRA FAILSAFE: Forcing result display");
      forceShowResults();
    }, 8000); // 8 seconds max wait time

    // First try: Use modern Fetch API
    fetch(lptAjax.ajaxurl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("Network response was not ok: " + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        clearTimeout(ultraFailsafeTimeout);
        console.log("Fetch API success:", data);
        processResponse(data);
      })
      .catch(function (error) {
        console.error("Fetch API error:", error);
        // If fetch fails, try XMLHttpRequest as backup
        sendWithXHR();
      });

    // Backup method: XMLHttpRequest
    function sendWithXHR() {
      console.log("Trying backup XMLHttpRequest method");
      var xhr = new XMLHttpRequest();
      xhr.open("POST", lptAjax.ajaxurl, true);
      xhr.timeout = 10000; // 10 seconds timeout

      xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            var response = JSON.parse(xhr.responseText);
            console.log("XHR success:", response);
            processResponse(response);
          } catch (error) {
            console.error("XHR parse error:", error);
            forceShowResults();
          }
        } else {
          console.error("XHR error status:", xhr.status);
          forceShowResults();
        }
      };

      xhr.onerror = function () {
        console.error("XHR network error");
        forceShowResults();
      };

      xhr.ontimeout = function () {
        console.error("XHR timeout");
        forceShowResults();
      };

      xhr.send(formData);
    }

    // Process server response
    function processResponse(response) {
      if (response && response.success) {
        hideTestQuestions();
        showTestResults(response.data);
      } else {
        console.error("Server reported error:", response);
        forceShowResults();
      }
    }

    // Force show results when all else fails
    function forceShowResults() {
      console.log("FORCE SHOWING RESULTS");
      hideTestQuestions();

      // Try to extract student info
      var studentName = "";
      try {
        var studentInfoRaw =
          document.getElementById("student_info")?.value || "{}";
        var studentInfo = JSON.parse(studentInfoRaw);
        studentName = studentInfo.name || "";
      } catch (e) {
        console.error("Error parsing student info:", e);
      }

      // Create a generic result
      var genericResult = {
        level: "A1", // Default level
        score: "?",
        total: questions.length,
        percentage: "?",
        student_info: {
          name: studentName,
        },
      };

      showTestResults(genericResult);
    }

    // Hide test questions
    function hideTestQuestions() {
      var testQuestions = document.getElementById("lpt-test-questions");
      if (testQuestions) {
        testQuestions.style.display = "none";
      }
    }

    // Show test results with enhanced reliability
    function showTestResults(data) {
      console.log("Showing test results:", data);

      // First, ensure the result div exists
      var resultDiv = document.getElementById("lpt-result");
      if (!resultDiv) {
        console.log("Result div not found, creating one");
        resultDiv = document.createElement("div");
        resultDiv.id = "lpt-result";

        // Find where to insert it
        var testContainer = document.querySelector(".lpt-test-container");
        if (testContainer) {
          testContainer.appendChild(resultDiv);
        } else {
          // Fallback: append to body
          document.body.appendChild(resultDiv);
        }
      }

      // Extract data with fallbacks
      var level = data && data.level ? data.level : "A1";
      var score = data && data.score ? data.score : "?";
      var total = data && data.total ? data.total : questions.length;
      var percentage = data && data.percentage ? data.percentage : "?";
      var name = "";

      if (data && data.student_info && data.student_info.name) {
        name = data.student_info.name;
      } else {
        // Try to get name from form
        try {
          var studentInfoRaw =
            document.getElementById("student_info")?.value || "{}";
          var studentInfo = JSON.parse(studentInfoRaw);
          name = studentInfo.name || "";
        } catch (e) {}
      }

      // Create result HTML with inline styles for maximum reliability
      resultDiv.innerHTML =
        '<div style="background-color: #ffffff; padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 800px; margin: 0 auto;">' +
        '<h3 style="color: #2c3e50; font-size: 24px; margin-bottom: 25px; font-weight: 700;">Seviye Tespit Sınavınız Tamamlandı</h3>' +
        '<div style="font-size: 72px; font-weight: 800; color: #3498db; margin: 30px 0;">' +
        level +
        "</div>" +
        '<div style="font-size: 18px; color: #34495e; margin-bottom: 20px; font-weight: 600;">' +
        score +
        "/" +
        total +
        " doğru (%" +
        percentage +
        " başarı)</div>" +
        '<div style="font-size: 16px; color: #5c6c7c; line-height: 1.6; margin-bottom: 30px;">Sayın <strong>' +
        name +
        "</strong>, seviye tespit sınavınız başarıyla tamamlanmıştır.</div>" +
        "</div>";

      // Ensure the div is visible with important flags
      resultDiv.style.cssText =
        "display: block !important; opacity: 1 !important; margin-top: 30px !important;";

      // Scroll to results
      setTimeout(function () {
        resultDiv.scrollIntoView({ behavior: "smooth", block: "start" });

        // Try to trigger confetti if available
        try {
          if (typeof confetti !== "undefined") {
            confetti({
              particleCount: 100,
              spread: 70,
              origin: { y: 0.6 },
            });
          }
        } catch (e) {
          console.log("Confetti effect not available");
        }
      }, 100);
    }
  },

  // Soruları adım adım gösterme fonksiyonu
  showQuestionsStepByStep: function () {
    var $ = jQuery;

    try {
      console.log("Enhanced showQuestionsStepByStep function called");

      // Direk DOM referanslarını al
      var testQuestionsDiv = document.getElementById("lpt-test-questions");
      var questions = document.querySelectorAll(".lpt-question");
      var progressBar = document.querySelector(".test-progress");

      console.log("Step-by-step DOM checks:", {
        testQuestionsDiv: testQuestionsDiv,
        questions: questions.length,
        progressBar: progressBar,
      });

      // Test formunu kesinlikle görünür yap
      if (testQuestionsDiv) {
        testQuestionsDiv.style.cssText =
          "display: block !important; visibility: visible !important; opacity: 1 !important;";
      } else {
        console.error("Test questions container still not found!");
        return;
      }

      // Tüm soruları gizle
      questions.forEach(function (question) {
        question.style.display = "none";
      });

      // İlk soruyu göster
      if (questions.length > 0) {
        questions[0].style.display = "block";
        console.log("First question display set to block");
      } else {
        console.error("No questions found!");
        return;
      }

      // Her soru için "Sonraki Soru" butonunu ekle (son soru hariç)
      for (var i = 0; i < questions.length - 1; i++) {
        // Mevcut bir butonu kaldır
        var oldButtons = questions[i].querySelectorAll(".next-question-btn");
        oldButtons.forEach(function (btn) {
          btn.parentNode.removeChild(btn);
        });

        // Sonraki buton oluştur
        var nextButton = document.createElement("button");
        nextButton.type = "button";
        nextButton.className = "next-question-btn";
        nextButton.innerHTML = "Sonraki Soru";
        nextButton.style.cssText =
          "background-color: #7FB3D5; color: white; border: none; padding: 10px 20px; font-size: 14px; border-radius: 5px; cursor: pointer; font-weight: 600; margin-top: 20px; transition: all 0.3s ease;";

        // Butonu soruya ekle
        questions[i].appendChild(nextButton);

        // Buton tıklama olayını ekle
        nextButton.addEventListener("click", function () {
          // Mevcut soruyu bul
          var currentQuestion = this.parentNode;
          var questionId = currentQuestion.getAttribute("data-question-id");
          var radioButtons = currentQuestion.querySelectorAll(
            'input[type="radio"]'
          );
          var answered = false;

          // Cevap verilmiş mi kontrol et
          for (var j = 0; j < radioButtons.length; j++) {
            if (radioButtons[j].checked) {
              answered = true;
              break;
            }
          }

          console.log("Next button clicked:", {
            questionId: questionId,
            answered: answered,
          });

          if (!answered) {
            alert("Lütfen bu soruyu cevaplayın.");
            return;
          }

          // Şu anki soru indeksini bul
          var currentIndex = Array.from(questions).indexOf(currentQuestion);
          var nextIndex = currentIndex + 1;

          console.log("Moving to question index:", nextIndex);

          // İlerleme çubuğunu güncelle
          LPTTest.updateStepProgress(nextIndex + 1, questions.length);

          // Şu anki soruyu gizle ve bir sonrakini göster
          currentQuestion.style.display = "none";
          if (nextIndex < questions.length) {
            questions[nextIndex].style.display = "block";

            // Son soru mu kontrol et
            if (nextIndex === questions.length - 1) {
              // Son sorunun stilini güncelle
              questions[nextIndex].style.backgroundColor = "#f0f9ff";
              questions[nextIndex].style.border = "1px solid #93c5fd";
            }

            // Soruya kaydır
            questions[nextIndex].scrollIntoView({
              behavior: "smooth",
              block: "start",
              inline: "nearest",
            });
            window.scrollBy(0, -50); // Biraz yukarı çek
          }
        });
      }

      // Son soruya "Testi Tamamla" butonunu ekle
      var lastQuestion = questions[questions.length - 1];

      // Mevcut bir butonu kaldır
      var oldActions = lastQuestion.querySelectorAll(".form-actions");
      oldActions.forEach(function (action) {
        action.parentNode.removeChild(action);
      });

      // Tamamla butonu oluştur
      var submitDiv = document.createElement("div");
      submitDiv.className = "form-actions";
      submitDiv.style.cssText =
        "margin-top: 30px; text-align: center; padding: 20px;";

      var submitButton = document.createElement("button");
      submitButton.type = "button";
      submitButton.id = "last-submit-btn";
      submitButton.className = "button button-primary";
      submitButton.innerHTML = "Testi Tamamla";
      submitButton.style.cssText =
        "background-color: #3498db; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2); min-width: 200px;";

      submitDiv.appendChild(submitButton);
      lastQuestion.appendChild(submitDiv);

      // Tamamla butonuna tıklama olayı ekle
      submitButton.addEventListener("click", function () {
        console.log("Submit button clicked");
        LPTTest.submitTest();
      });

      // Orijinal butonları gizle
      var originalActions = document.querySelectorAll(
        "#lpt-test-form > .form-actions"
      );
      originalActions.forEach(function (action) {
        action.style.display = "none";
      });

      // İlerleme çubuğunu güncelle
      LPTTest.updateStepProgress(1, questions.length);

      console.log("Step-by-step setup complete");
    } catch (error) {
      console.error("Error in showQuestionsStepByStep:", error);
      alert("Sınav soruları yüklenirken bir hata oluştu: " + error.message);
    }
  },

  // Adım ilerlemesini güncelle
  updateStepProgress: function (currentStep, totalSteps) {
    try {
      console.log("Updating progress:", {
        currentStep: currentStep,
        totalSteps: totalSteps,
      });

      // DOM elementlerine direk eriş
      var progressFill = document.querySelector(".progress-fill");
      var progressText = document.querySelector(".progress-text");
      var progressTitle = document.querySelector(".test-progress h3");

      if (!progressFill || !progressText || !progressTitle) {
        console.error("Progress bar elements not found");
        return;
      }

      // Ilerleme yüzdesini hesapla
      var progressPercentage = (currentStep / totalSteps) * 100;

      // Progress bar ve metni güncelle
      progressFill.style.width = progressPercentage + "%";
      progressText.textContent =
        currentStep +
        " / " +
        totalSteps +
        " soru (" +
        Math.round(progressPercentage) +
        "%)";

      // Başlığı güncelle
      progressTitle.textContent = "Sınav İlerlemesi: " + currentStep + ". Soru";

      console.log("Progress updated successfully");
    } catch (error) {
      console.error("Error updating progress:", error);
    }
  },
};

// Initialize the test system
LPTTest.init();
