<?php
session_start();

// التحقق مما إذا كان المستخدم قد سجل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html"); // إذا لم يكن مسجلاً، يتم نقله إلى صفحة تسجيل الدخول
    exit();
}

// جلب بيانات المستخدم من الجلسة
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$team_name = $_SESSION['team_name'];

// الاتصال بقاعدة البيانات
$host = 'fdb1030.awardspace.net';
$db   = '4584173_seif';
$user = '4584173_seif';
$pass = 'Sseeiiff1@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// جلب آخر زيارة فقط من قاعدة البيانات
$stmt_visits = $pdo->query("SELECT * FROM الزيارات ORDER BY id DESC LIMIT 1");
$visit = $stmt_visits->fetch(PDO::FETCH_ASSOC);

$is_admin = ($team_name === 'admin');


// جلب بيانات المتطوعين من قاعدة البيانات
$stmt_volunteers = $pdo->query("SELECT الاسم_الكامل, اسم_التيم FROM المتطوعين");
$volunteers = $stmt_volunteers->fetchAll(PDO::FETCH_ASSOC);

// جلب آخر معرض فقط من قاعدة البيانات
$stmt_exhibitions = $pdo->query("SELECT * FROM المعارض ORDER BY id DESC LIMIT 1");
$exhibition = $stmt_exhibitions->fetch(PDO::FETCH_ASSOC);

$stmt_posts = $pdo->query("SELECT * FROM team_insan_posts ORDER BY id DESC LIMIT 1");
$post = $stmt_posts->fetch(PDO::FETCH_ASSOC);

// معالجة الحجز عند الضغط على الزر (الزيارات)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reserve_visit'])) {
    $visit_id = $_POST['visit_id'];

    // التحقق مما إذا كان المستخدم قد سجل في الزيارة مسبقًا
    $check_stmt = $pdo->prepare("SELECT * FROM المشاركون_في_الزيارات WHERE user_id = ? AND visit_id = ?");
    $check_stmt->execute([$user_id, $visit_id]);

    if ($check_stmt->rowCount() == 0) {
        // إدخال بيانات الحجز في الجدول
        $reserve_stmt = $pdo->prepare("INSERT INTO المشاركون_في_الزيارات (user_id, الاسم, اسم_التيم, visit_id) VALUES (?, ?, ?, ?)");
        $reserve_stmt->execute([$user_id, $user_name, $team_name, $visit_id]);
        $reservation_success = "✅ تم حجز الزيارة بنجاح!";
    } else {
        $reservation_success = "⚠️ لقد قمت بحجز هذه الزيارة مسبقًا.";
    }
}

// معالجة المشاركة عند الضغط على الزر (المعارض)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['join_exhibition'])) {
    $exhibition_id = $_POST['exhibition_id'];

    // التحقق مما إذا كان المستخدم قد سجل في المعرض مسبقًا
    $check_stmt = $pdo->prepare("SELECT * FROM المشاركون_في_المعارض WHERE user_id = ? AND معرض_id = ?");
    $check_stmt->execute([$user_id, $exhibition_id]);

    if ($check_stmt->rowCount() == 0) {
        // إدخال بيانات المشاركة في الجدول
        $join_stmt = $pdo->prepare("INSERT INTO المشاركون_في_المعارض (user_id, الاسم, اسم_التيم, معرض_id) VALUES (?, ?, ?, ?)");
        $join_stmt->execute([$user_id, $user_name, $team_name, $exhibition_id]);
        $join_success = "✅ تمت المشاركة في المعرض بنجاح!";
    } else {
        $join_success = "⚠️ لقد قمت بالمشاركة في هذا المعرض مسبقًا.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
            

    <title>Life Makers</title>
    <script>
        // دالة لفتح وإغلاق نافذة الدردشة
        function toggleChat() {
            const chatWindow = document.getElementById('chatWindow');
            chatWindow.style.display = chatWindow.style.display === 'none' ? 'flex' : 'none';
            if (chatWindow.style.display === 'flex') {
                loadMessages();
            }
        }

   // دالة لتوليد لون بناءً على user_id
function getUserColor(userId) {
    const colors = [
        "#e6194B", "#3cb44b", "#ffe119", "#4363d8", "#f58231",
        "#911eb4", "#42d4f4", "#f032e6", "#bfef45", "#fabebe",
        "#469990", "#e6beff", "#9A6324", "#800000", "#aaffc3",
        "#808000", "#000075", "#a9a9a9", "#ffffff", "#000000"
    ];
    return colors[userId % colors.length]; // اختيار لون بناءً على user_id
}

// دالة لتحميل الرسائل مع الألوان
function loadMessages() {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML = '';

    fetch('get_messages.php')
        .then(response => response.json())
        .then(messages => {
            messages.forEach(message => {
                const messageElement = document.createElement('div');
                messageElement.className = 'message';
                const userColor = getUserColor(message.user_id);
                const isMyMessage = message.user_id == <?php echo json_encode($user_id); ?>;
                const isAdmin = <?php echo json_encode($team_name === 'admin'); ?>;

                messageElement.classList.add(isMyMessage ? 'my-message' : 'other-message');

                const timestamp = new Date(message.timestamp).toLocaleTimeString();

                messageElement.innerHTML = `
                    <div class="text">
                        <div class="sender" style="color: ${userColor}; font-weight: bold;">
                            ${message.user_name} <span style="font-size: 0.5em;">(${message.team_name})</span>
                        </div>
                        ${message.message}
                        <div class="timestamp" style="font-size: 0.8em; color: #777; margin-top: 5px;">
                            ${timestamp}
                        </div>
                    </div>
                    ${(isMyMessage || isAdmin) ? `<button class="delete-btn" onclick="deleteMessage(${message.id})">🗑️</button>` : ''}
                    ${isAdmin ? `<button class="permanent-delete-btn" onclick="permanentDeleteMessage(${message.id})">❌</button>` : ''}
                `;
                chatMessages.appendChild(messageElement);
            });
            chatMessages.scrollTop = chatMessages.scrollHeight;
        });
}
            
            
            
            function permanentDeleteMessage(messageId) {
    if (confirm("هل أنت متأكد من الحذف النهائي لهذه الرسالة؟ لا يمكن التراجع عن هذا الإجراء!")) {
        fetch('permanent_delete_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message_id: messageId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadMessages(); // إعادة تحميل الرسائل بعد الحذف
            } else {
                alert("فشل الحذف النهائي للرسالة: " + data.message);
            }
        });
    }
}
            
            
            
            
            
            function deleteMessage(messageId) {
    if (confirm("هل أنت متأكد من حذف هذه الرسالة؟")) {
        fetch('delete_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message_id: messageId,
                user_id: <?php echo $user_id; ?>,
                is_admin: <?php echo json_encode($is_admin); ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadMessages(); // إعادة تحميل الرسائل بعد الحذف
            } else {
                alert("فشل حذف الرسالة: " + data.message);
            }
        });
    }
}
            
            window.onload = function() {
    const audio = document.getElementById('welcomeSound');
    audio.play().catch(error => {
        console.error("تعذر تشغيل الصوت تلقائيًا:", error);
    });
};
            
            
            
         function toggleVolunteersList() {
    const volunteersList = document.getElementById('volunteersList');
    volunteersList.style.display = (volunteersList.style.display === 'none' || volunteersList.style.display === '') ? 'block' : 'none';
}
            
             function showTable() {
            var tableContainer = document.getElementById("table-container");
            tableContainer.style.display = "block";
            fetch('get_logins.php') // استدعاء ملف PHP لجلب البيانات
                .then(response => response.json())
                .then(data => {
                    let table = document.getElementById("login-table");
                    table.innerHTML = "<tr><th>اسم المستخدم</th><th>وقت الدخول</th></tr>"; // رأس الجدول
                    data.forEach(row => {
                        let tr = document.createElement("tr");
                        tr.innerHTML = `<td>${row.user_name}</td><td>${row.timestamp}</td>`;
                        table.appendChild(tr);
                    });
                })
                .catch(error => console.log(error));
        }

        // دالة لإغلاق الجدول
        function closeTable() {
            var tableContainer = document.getElementById("table-container");
            tableContainer.style.display = "none";
        }

            // دالة لإظهار وإخفاء قائمة التحكم
function toggleControlMenu() {
    const controlMenu = document.getElementById('controlMenu');
    controlMenu.style.display = controlMenu.style.display === 'none' ? 'block' : 'none';
}




        // دالة لإرسال الرسائل
        function sendMessage() {
            const messageInput = document.getElementById('chatMessage');
            const message = messageInput.value.trim();

            if (message) {
                fetch('send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: <?php echo $user_id; ?>,
                        user_name: '<?php echo $user_name; ?>',
                        team_name: '<?php echo $team_name; ?>',
                        message: message
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageInput.value = '';
                        loadMessages();
                    }
                });
            }
        }
            
            function toggleReportForm() {
    const reportWindow = document.getElementById('reportWindow');
    reportWindow.style.display = reportWindow.style.display === 'none' ? 'flex' : 'none';
}
            
            
            function playSound() {
    const audio = document.getElementById('welcomeSound');
    audio.play().catch(error => {
        console.error("تعذر تشغيل الصوت:", error);
    });
}
            
            function toggleSound() {
    const audio = document.getElementById('welcomeSound');
    const playPauseButton = document.getElementById('playPauseButton');

    if (audio.paused) {
        audio.play();
        playPauseButton.textContent = "⏯ اضغط لاقاف الصوت";
    } else {
        audio.pause();
        playPauseButton.textContent = "▶ اضغط لتشغيل الصوت ";
    }
}
            
            
            

function submitReport() {
    const problemDescription = document.getElementById('problemDescription').value.trim();

    if (problemDescription) {
        fetch('submit_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: <?php echo $user_id; ?>,
                user_name: '<?php echo $user_name; ?>',
                team_name: '<?php echo $team_name; ?>',
                problem_description: problemDescription
            })
        })
        .then(response => response.json())
        .then(data => {
            // إذا كانت الاستجابة ناجحة، قم بتفريغ محتويات مربع النص
            document.getElementById('problemDescription').value = '';

            // إغلاق نافذة التقرير
            toggleReportForm();

            // يمكنك إضافة إشعار أو أي إجراء آخر هنا بعد إرسال التقرير
            alert('تم إرسال التقرير بنجاح!');
        })
        .catch(error => {
            // التعامل مع الأخطاء في حال حدوثها
            console.error('Error:', error);
            alert('حدث خطأ أثناء إرسال التقرير.');
        });
    } else {
        alert('يرجى كتابة وصف المشكلة قبل الإرسال.');
    }
}



        // تحميل الرسائل كل 10 ثوانٍ
        setInterval(loadMessages, 20000);
    </script>
    <style>
       body {
    font-family: 'Tajawal', Arial, sans-serif;
    direction: rtl;
    text-align: center;
    background: url('back.png') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #333;
}

        .navbar {
            background-color: #ffffff;
            padding: 15px 30px;
            box-shadow: 0px 2px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar img {
            height: 80px;
            margin-left: 20px;
        }
        .navbar h1 {
            font-family: 'Traditional Arabic', Arial, sans-serif;
            font-size: 40px;
            color: #003366;
            margin: 0;
            font-weight: bold;
        }
        .user-info {
            text-align: left;
            padding-left: 10px;
            font-size: 15px;
            font-weight: bold;
            color: #003366;
        }
        .container {
            width: 60%;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #003366;
            font-size: 28px;
            margin-bottom: 25px;
            font-weight: bold;
            position: relative;
        }
        h2::after {
            content: '';
            width: 50px;
            height: 3px;
            background-color: #007bff;
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }
        .card {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            text-align: right;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 8px 25px rgba(0, 0, 0, 0.15);
        }
        .card p {
            margin: 12px 0;
            font-size: 18px;
            color: #555;
        }
        .card strong {
            color: #007bff;
            font-weight: bold;
        }
        .card img {
            max-width: 100%;
            border-radius: 10px;
            margin-top: 15px;
            border: 1px solid #e0e0e0;
        }
        .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 18px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 15px;
            display: inline-block;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        .success-message {
            color: #28a745;
            font-size: 18px;
            margin-top: 15px;
            font-weight: bold;
        }
        .visit-details {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .no-data {
            color: #888;
            font-size: 18px;
            font-style: italic;
        }
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .chat-window {
            position: fixed;
            bottom: 90px;
            right: 20px;
            width: 300px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
            display: none;
            flex-direction: column;
            z-index: 1000;
        }
        .chat-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-messages {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
            max-height: 300px;
        }
        .chat-input {
            display: flex;
            padding: 10px;
            border-top: 1px solid #e0e0e0;
        }
        .chat-input input {
            flex: 1;
            padding: 8px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-right: 10px;
        }
        .chat-input button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
        .message {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.message .text {
    padding: 8px;
    border-radius: 10px;
    max-width: 70%;
    word-wrap: break-word;
}

.my-message {
    justify-content: flex-start;
}

.my-message .text {
    background-color: #56a5ec;
    color: white;
    text-align: right;
}

.other-message {
    justify-content: flex-end;
}

.other-message .text {
    background-color: #f1f1f1;
    color: black;
    text-align: left;
}
.logout-btn {
    background-color: #dc3545; /* لون أحمر */
    color: white;
    border: none;
    padding: 0px 5px;
    font-size: 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 0px; /* إضافة هامش أعلى */
}

.logout-btn:hover {
    background-color: #c82333; /* لون أغمق عند التحويم */
    transform: scale(1.05);
}
            
            
            .fab-volunteers {
    position: fixed;
    bottom: 90px;
    left: 20px;
    background-color: #28a745;
    color: white;
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.volunteers-window {
    position: fixed;
    bottom: 160px;
    left: 20px;
    width: 250px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
}

.volunteers-header {
    background-color: #28a745;
    color: white;
    padding: 10px;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: bold;
}

.volunteers-header button {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
}

.volunteers-content {
    padding: 10px;
    font-size: 14px;
}
            
            .sub-title {
  font-size: 16px; /* حجم النص للجملة */
  text-align: center;
  color: #000000; /* يمكن تغيير اللون حسب الرغبة */
}

            
.fab-report {
    position: fixed;
    bottom: 850px; /* تعديل هذه القيمة حسب الحاجة */
    left: 20px;
    background-color: #ff4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.report-window {
    position: fixed;
    bottom: 580px;
    left: 20px;
    width: 300px;
    background-color: white;
    border-radius: 10px;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    z-index: 1000;
}

.report-header {
    background-color: #ff4444;
    color: white;
    padding: 10px;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: bold;
}

.report-content {
    padding: 10px;
    display: flex;
    flex-direction: column;
}

.report-content textarea {
    width: 95%;
    height: 100px;
    padding: 8px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    margin-bottom: 10px;
    font-size: 14px;
    resize: none;
}

.report-content button {
    background-color: #ff4444;
    color: white;
    border: none;
    padding: 10px 12px;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.report-content button:hover {
    background-color: #e03939;
}
   
             /* تصميم الزر العائم */
.floating-btn {
    position: fixed;
    bottom: 800px; /* موضع الزر كما هو */
    right: 20px;
    background-color: #ff6600;
    color: white;
    border: none;
    padding: 15px;
    font-size: 24px;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    transition: background-color 0.3s, transform 0.3s;
}

.floating-btn:hover {
    background-color: #ff5500;
    transform: scale(1.1);
}

/* تنسيق نافذة القائمة */
.table-container {
    display: none;
    position: fixed;
    top: 320px; /* تم تعديل موضع القائمة لتكون أسفل الزر بـ 60 بكسل */
    right: 20px;
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    width: 300px;
    max-height: 400px;
    overflow-y: auto;
    z-index: 0;
    padding: 15px;
    font-size: 14px;
    border: 1px solid #e0e0e0;
    transition: opacity 0.3s ease-in-out;
}



        .table-container.show {
            display: block;
            opacity: 1;
        }

        /* تنسيق الجدول */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 10px;
        }

        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        /* تنسيق زر الإغلاق */
        .close-btn {
            text-align: left;
            cursor: pointer;
            font-size: 18px;
            color: #ff6600;
            margin-bottom: 10px;
            display: inline-block;
        }

        .close-btn:hover {
            color: #ff5500;
        }

        /* تنسيق العنوان */
        h10 {
            color: #007bff;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            display: block;
        }
            
            .permanent-delete-btn {
    background: none;
    border: none;
    color: #dc3545; /* لون أحمر */
    cursor: pointer;
    font-size: 12px;
    margin-left: 5px;
    padding: 0;
}

.permanent-delete-btn:hover {
    color: #c82333; /* لون أحمر أغمق عند التحويم */
}
            
            
            /* زر التحكم العائم */
/* زر التحكم العائم */
.fab-control {
    position: fixed;
    bottom: 20px; /* المسافة من الأسفل */
    left: 50%; /* توسيط أفقي */
    transform: translateX(-50%); /* تعديل المركز */
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    font-size: 24px;
    cursor: pointer;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.fab-control:hover {
    background-color: #0056b3;
}

/* قائمة التحكم المنبثقة */
.control-menu {
    position: fixed;
    bottom: 90px; /* المسافة من الأسفل (فوق الزر) */
    left: 50%; /* توسيط أفقي */
    transform: translateX(-50%); /* تعديل المركز */
    background-color: white;
    border-radius: 10px;
    box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
    display: none;
    flex-direction: column;
    z-index: 1000;
    width: 200px;
    padding: 10px;
    text-align: center; /* توسيط النص */
}

.control-menu ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.control-menu ul li {
    padding: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.control-menu ul li:last-child {
    border-bottom: none;
}

.control-menu ul li a {
    text-decoration: none;
    color: #333;
    font-size: 16px;
}

.control-menu ul li a:hover {
    color: #007bff;
}          
            
            
            

        
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
</head>
<body id="chatBody">
        <button class="fab-volunteers" onclick="toggleVolunteersList()">👥</button>


    <!-- الشريط العلوي -->
      <div class="navbar">
        <div class="user-info">
            <p>مرحبًا، <?php echo htmlspecialchars($user_name); ?></p>
            <p> <?php echo htmlspecialchars($team_name); ?></p>
                <form action="https://lifemakers1.atwebpages.com/index.html" method="post">
        <button type="submit" class="logout-btn">تسجيل الخروج</button>
    </form>
        </div>
  <div class="header">
  <h1 class="main-title">صناع الحياة</h1>
  <p class="sub-title">قُلْ هُوَ اللَّهُ أَحَدٌ، اللَّهُ الصَّمَدُ، لَمْ يَلِدْ وَلَمْ يُولَدْ، وَلَمْ يَكُنْ لَهُۥۤ كُفُوًا أَحَدٌ</p>
  <button id="playPauseButton" onclick="toggleSound()">▶اضغط لتشغيل الصوت</button>
</div>

        <img src="logo.gif" alt="Life Makers Logo">

    </div>


    <!-- قسم الزيارات -->
    <div class="container">
        <h2>📅 الزيارة التالية</h2>

        <?php if ($visit): ?>
            <div class="card">
                <div class="visit-details">
                    <p><strong>📍 المكان:</strong> <?php echo htmlspecialchars($visit['المكان']); ?></p>
                    <p><strong>📆 التاريخ:</strong> <?php echo htmlspecialchars($visit['التاريخ']); ?></p>
                    <p><strong>⏰ وقت التجمع:</strong> <?php echo htmlspecialchars($visit['وقت_التجمع']); ?></p>
                    <p><strong>📍 مكان التجمع:</strong> <?php echo htmlspecialchars($visit['مكان_التجمع']); ?></p>
                    <p><strong>👤 المشرف:</strong> <?php echo htmlspecialchars($visit['المشرف']); ?></p>
                </div>

                <form method="post">
                    <input type="hidden" name="visit_id" value="<?php echo $visit['id']; ?>">
                    <button type="submit" name="reserve_visit" class="btn">📝 حجز الزيارة</button>
                </form>
            </div>
        <?php else: ?>
            <p class="no-data">لا توجد زيارات متاحة في الوقت الحالي.</p>
        <?php endif; ?>

        <?php if (isset($reservation_success)): ?>
            <p class="success-message"><?php echo $reservation_success; ?></p>
        <?php endif; ?>
    </div>

    <!-- قسم المعارض -->
    <div class="container">
        <h2>🎪 المعرض التالي</h2>

        <?php if ($exhibition): ?>
            <div class="card">
                <div class="visit-details">
                    <p><strong>📍 مكان المعرض:</strong> <?php echo htmlspecialchars($exhibition['المكان']); ?></p>
                    <p><strong>📆 التاريخ:</strong> <?php echo htmlspecialchars($exhibition['التاريخ']); ?></p>
                    <p><strong>⏰ الوقت:</strong> <?php echo htmlspecialchars($exhibition['الوقت']); ?></p>
                    <p><strong>👤 المشرف:</strong> <?php echo htmlspecialchars($exhibition['المشرف']); ?></p>
                </div>

                <form method="post">
                    <input type="hidden" name="exhibition_id" value="<?php echo $exhibition['id']; ?>">
                    <button type="submit" name="join_exhibition" class="btn">🎪 المشاركة في المعرض</button>
                </form>
            </div>
        <?php else: ?>
            <p class="no-data">لا توجد معارض متاحة في الوقت الحالي.</p>
        <?php endif; ?>

        <?php if (isset($join_success)): ?>
            <p class="success-message"><?php echo $join_success; ?></p>
        <?php endif; ?>
    </div>

    <!-- قسم تقارير فريق إنسان -->
    <div class="container">
        <h2>📝 آخر تقرير لفريق إنسان</h2>

        <?php if ($post): ?>
            <div class="card">
                <p><strong>📄 التقرير:</strong> <?php echo htmlspecialchars($post['content']); ?></p>
                <?php if ($post['image']): ?>
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="صورة التقرير">
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="no-data">لا توجد تقارير متاحة في الوقت الحالي.</p>
        <?php endif; ?>
    </div>

    <!-- إضافة الزر العائم -->
    <div class="fab" onclick="toggleChat()">💬</div>

    <!-- نافذة الدردشة -->
    <div id="chatWindow" class="chat-window">
        <div class="chat-header">
            <h3>الدردشة</h3>
            <button onclick="toggleChat()">✖️</button>
        </div>
        <div id="chatMessages" class="chat-messages"></div>
        <div class="chat-input">
            <input type="text" id="chatMessage" placeholder="اكتب رسالتك هنا..." />
            <button onclick="sendMessage()">إرسال</button>
        </div>
    </div>
   

<div id="volunteersList" class="volunteers-window">
    <div class="volunteers-header">
        <span>المتطوعون</span>
        <button onclick="toggleVolunteersList()">✖</button>
    </div>
 
    <div class="volunteers-content">
        <?php foreach ($volunteers as $volunteer): ?>
            <p><strong><?php echo htmlspecialchars($volunteer['الاسم_الكامل']); ?></strong> - <?php echo htmlspecialchars($volunteer['اسم_التيم']); ?></p>
        <?php endforeach; ?>
    </div>
</div>
      <!-- إضافة الزر العائم للإبلاغ عن مشكلة -->
<button class="fab-report" onclick="toggleReportForm()">⚠️</button>

<!-- نافذة الإبلاغ عن مشكلة -->
<div id="reportWindow" class="report-window">
    <div class="report-header">
        <h3>الإبلاغ عن مشكلة في الموقع</h3>
        <button onclick="toggleReportForm()">✖️</button>
    </div>
    <div class="report-content">
        <textarea id="problemDescription" placeholder="صف المشكلة التي تواجهها..."></textarea>
        <button onclick="submitReport()">إرسال</button>
    </div>
</div>
        
   <?php if ($is_admin): ?>
        <button class="floating-btn" onclick="showTable()">📋</button>
    <?php endif; ?>

    <!-- الجدول الذي سيظهر عند الضغط على الزر -->
    <div class="table-container" id="table-container">
        <span class="close-btn" onclick="closeTable()">إغلاق</span>
        <h10>تسجيل الدخول لآخر 3 ساعات</h10>
        <table id="login-table">
            <!-- سيتم إضافة البيانات هنا باستخدام JavaScript -->
        </table>
    </div>
        
        <!-- زر التحكم العائم -->

        
        <?php if ($team_name === 'admin'): ?>
    <!-- زر التحكم العائم -->
    <button class="fab-control" onclick="toggleControlMenu()">⚙️</button>

    <!-- قائمة التحكم المنبثقة -->
    <div id="controlMenu" class="control-menu">
        <ul>
        <li><a href="https://lifemakers1.atwebpages.com/admin.php">صفحة قبول طلبات تسجيل الدخول</a></li>
        <li><a href="https://lifemakers1.atwebpages.com/add_exhibition.html">صفحة اضافه معرض</a></li>
        <li><a href="https://lifemakers1.atwebpages.com/team_leader.php">صفحة اضافه زياره </a></li>
        <li><a href="https://lifemakers1.atwebpages.com/add_post.php">صفحة اضافه تقارير تيم انسان </a></li>
        </ul>
    </div>
<?php endif; ?>
        
        <audio id="welcomeSound" autoplay>
    <source src="sound.m4a" type="audio/mp4">
    المتصفح الخاص بك لا يدعم تشغيل الصوت.
</audio>
  
 
</body>
</html>