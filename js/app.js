/**
 * app.js - JavaScript chính cho ứng dụng quản lý đỗ xe thông minh
 */

// Sử dụng strict mode để tránh lỗi phổ biến
"use strict";

// --- Hàm tiện ích để gọi API (fetchApi) ---
async function fetchApi(endpoint, options = {}) {
  const defaultHeaders = {
    "Content-Type": "application/json",
    Accept: "application/json",
    // 'Authorization': 'Bearer YOUR_API_KEY' // Thêm nếu cần xác thực API
  };
  const config = {
    method: options.method || "GET",
    headers: { ...defaultHeaders, ...options.headers },
    ...options,
  };

  if ((config.method === "POST" || config.method === "PUT") && config.body && typeof config.body !== 'string') {
    if (!(config.body instanceof FormData)) {
      config.body = JSON.stringify(config.body);
    } else {
      delete config.headers['Content-Type']; // Để trình duyệt tự set cho FormData
    }
  }
  if (config.method === "GET" && config.body) {
    delete config.body;
  }

  const baseUrl = "/parking-manager/"; // Đảm bảo đúng
  const url = `${baseUrl}api/${endpoint}`;

  try {
    const response = await fetch(url, config);

    if (!response.ok) {
      const errorText = await response.text();
      let errorData = { message: errorText || `Lỗi HTTP: ${response.status}` };
      try {
        const jsonData = JSON.parse(errorText);
        if (jsonData?.message) { errorData = jsonData; }
        else if (typeof jsonData === 'object' && jsonData !== null) { errorData = jsonData; }
      } catch (parseError) { /* Ignore */ }
      console.error('API Error Response:', errorData);
      let errorMessage = `Lỗi API (Status: ${response.status})`;
      if (errorData?.message) { errorMessage = errorData.message; }
      else if (typeof errorData === 'string') { errorMessage = errorData; }
      throw new Error(errorMessage);
    }

    if (response.status === 204) { return null; }

    const contentType = response.headers.get("content-type");
    if (contentType?.includes("application/json")) {
      return await response.json();
    } else {
      return await response.text(); // Trả về text nếu không phải JSON
    }

  } catch (error) {
    console.error("Fetch API Error:", error);
    if (error instanceof Error) { throw error; }
    else { throw new Error(error.message || String(error)); }
  }
}

// --- Hàm hiển thị thông báo (showAlert) ---
function showAlert(message, type = "info", containerId = "alert-container") {
    const alertContainer = document.getElementById(containerId);
    if (!alertContainer) {
      console.warn(`Alert container with id '${containerId}' not found.`);
      alert(`${type.toUpperCase()}: ${message}`); // Fallback
      return;
    }
    const wrapper = document.createElement("div");
    wrapper.innerHTML = `
          <div class="alert alert-${type} alert-dismissible fade show" role="alert">
              ${message}
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
      `;
    alertContainer.append(wrapper);
    const alertElement = wrapper.querySelector('.alert');
     if (alertElement) {
         setTimeout(() => {
             const alertInstance = bootstrap.Alert.getOrCreateInstance(alertElement);
             if (alertInstance) { alertInstance.close(); }
         }, 5000); // Tự ẩn sau 5 giây
     }
  }

// --- Hàm định dạng khoảng thời gian ---
function formatDuration(milliseconds) {
    if (milliseconds <= 0) return "(Vừa vào)";

    const seconds = Math.floor((milliseconds / 1000) % 60);
    const minutes = Math.floor((milliseconds / (1000 * 60)) % 60);
    const hours = Math.floor((milliseconds / (1000 * 60 * 60)) % 24);
    const days = Math.floor(milliseconds / (1000 * 60 * 60 * 24));

    let parts = [];
    if (days > 0) parts.push(`${days} ngày`);
    if (hours > 0) parts.push(`${hours} giờ`);
    if (minutes > 0 && (days > 0 || hours > 0 || minutes > 0)) parts.push(`${minutes} phút`);
    if (days === 0 && hours === 0 && minutes === 0 && seconds >= 0) { // Hiển thị giây nếu < 1 phút
        // parts.push(`${seconds} giây`); // Có thể thêm giây nếu muốn
        if (parts.length === 0 && seconds < 10) return "(Vừa vào)"; // Coi như vừa vào nếu < 10s
        if (parts.length === 0) parts.push("Dưới 1 phút"); // Thay vì giây
    }
    if (parts.length === 0 && minutes > 0) parts.push(`${minutes} phút`); // Đảm bảo phút luôn hiển thị nếu > 0
    if (parts.length === 0) return "(Vừa vào)"; // Trường hợp khác

    return `(${parts.join(' ')})`;
}


// --- Logic cho trang Dashboard ---
function initDashboard() {
  const dashboardContainer = document.getElementById("dashboard-container");
  if (!dashboardContainer) return;

  console.log("Initializing Dashboard...");
  let dashboardUpdateInterval = null; // *** SỬA: Dùng let thay vì const ***
  let parkingChart = null;          // *** SỬA: Dùng let thay vì const ***

  const overviewElements = {
    total: document.getElementById("total-slots"),
    occupied: document.getElementById("occupied-slots"),
    available: document.getElementById("available-slots"),
    espStatus: document.getElementById("esp-connection-status"),
  };
  const slotsContainer = document.getElementById("slots-container");
  const openBarrierBtn = document.getElementById("openBarrierBtn");
  const parkingChartCanvas = document.getElementById("parkingChart");


  async function updateDashboardData() {
    // console.log("Fetching dashboard data...");
    try {
      const data = await fetchApi("get_status.php");

      if (data && data.status === "success") {
        // 1. Cập nhật tổng quan
        if (overviewElements.total) overviewElements.total.textContent = data.overview.total || 0;
        if (overviewElements.occupied) overviewElements.occupied.textContent = data.overview.occupied || 0;
        if (overviewElements.available) overviewElements.available.textContent = data.overview.available || 0;

        if (overviewElements.espStatus) {
          let statusText = "Không rõ";
          let statusClass = "text-muted";
          switch (data.overview.esp_connection) {
            case "online": statusText = "Đang kết nối"; statusClass = "text-success"; break;
            case "offline": statusText = "Mất kết nối"; statusClass = "text-danger"; break;
            default: statusText = "Không rõ"; statusClass = "text-warning";
          }
          overviewElements.espStatus.textContent = `Trạng thái ESP: ${statusText}`;
          overviewElements.espStatus.className = `card-text fw-bold ${statusClass}`;
        }

        // 2. Cập nhật Slots
        if (slotsContainer && data.slots) {
          slotsContainer.innerHTML = "";
          data.slots.forEach((slot) => {
            const slotDiv = document.createElement("div");
            slotDiv.id = `slot-${slot.slot_id}`;
            slotDiv.classList.add("col-lg-3", "col-md-4", "col-sm-6", "mb-3");

            let cardClass = "bg-light text-dark";
            let statusIcon = "fas fa-question-circle text-muted";
            let statusText = "Không xác định";

            switch (slot.status) {
              case "available":
                cardClass = "bg-success-subtle text-success-emphasis";
                statusIcon = "fas fa-check-circle text-success";
                statusText = "Trống";
                break;
              case "occupied":
                cardClass = "bg-danger-subtle text-danger-emphasis";
                statusIcon = "fas fa-times-circle text-danger";
                const userInfo = slot.user_name ? `(${slot.user_name})` : "(Xe không xác định)"; // Sửa lại nếu UID là NULL
                statusText = `Đã đỗ ${userInfo}`;
                if (slot.occupied_since) {
                  const occupiedTimestamp = new Date(slot.occupied_since).getTime();
                  const nowTimestamp = new Date().getTime();
                  const durationString = formatDuration(nowTimestamp - occupiedTimestamp);
                  const occupiedTimeFormatted = new Date(occupiedTimestamp).toLocaleString("vi-VN", { dateStyle: 'short', timeStyle: 'short' });
                  statusText += `<br><small class="text-muted">Từ: ${occupiedTimeFormatted} ${durationString}</small>`;
                } else {
                   statusText += `<br><small class="text-muted">Từ: Không rõ</small>`;
                }
                break;
               case "reserved": cardClass = "bg-warning-subtle text-warning-emphasis"; statusIcon = "fas fa-pause-circle text-warning"; statusText = "Đặt trước"; break;
               case "maintenance": cardClass = "bg-secondary-subtle text-secondary-emphasis"; statusIcon = "fas fa-tools text-secondary"; statusText = "Bảo trì"; break;
            }

            const specialIndicator = slot.is_special ? '<i class="fas fa-star text-warning ms-1" title="Vị trí đặc biệt"></i>' : "";
            if (slot.is_special) { cardClass += " border border-warning border-2"; }

            slotDiv.innerHTML = `
                 <div class="card h-100 shadow-sm ${cardClass}">
                     <div class="card-body d-flex flex-column justify-content-center align-items-center">
                         <h5 class="card-title mb-1">${slot.slot_name}${specialIndicator}</h5>
                         <p class="card-text text-center small mb-0">
                             <i class="${statusIcon} me-1"></i> ${statusText}
                         </p>
                     </div>
                 </div>
             `;
            slotsContainer.appendChild(slotDiv);
          });
        }

        // 3. Cập nhật biểu đồ Chart.js
        if (parkingChart) {
          const { available = 0, occupied = 0, reserved = 0 } = data.overview; // Đảm bảo có giá trị 0 nếu thiếu
          // Kiểm tra xem dữ liệu có thay đổi không trước khi update (tùy chọn)
           const currentData = parkingChart.data.datasets[0].data;
           if (currentData[0] !== available || currentData[1] !== occupied || currentData[2] !== reserved) {
                 parkingChart.data.datasets[0].data = [available, occupied, reserved];
                 parkingChart.update();
                 // console.log('Chart updated');
           }
        } else if (parkingChartCanvas) {
             // Khởi tạo chart nếu chưa có và canvas đã tồn tại
             initializeParkingChart(data.overview);
        }

      } else {
        // API trả về status không phải success
         throw new Error(data.message || data || "Phản hồi API không hợp lệ."); // Ném lỗi với message hoặc cả data nếu ko có msg
      }
    } catch (error) {
      console.error("Error updating dashboard:", error);
      showAlert(`Lỗi cập nhật dashboard: ${error.message}`, "danger");
    }
  }

  // Hàm khởi tạo biểu đồ (tách ra để gọi khi cần)
  function initializeParkingChart(overviewData = {}) {
       if (!parkingChartCanvas || parkingChart) return; // Không tạo nếu không có canvas hoặc đã tạo rồi

       const { available = 0, occupied = 0, reserved = 0 } = overviewData; // Lấy dữ liệu hoặc mặc định là 0
       const ctx = parkingChartCanvas.getContext("2d");
       parkingChart = new Chart(ctx, { // Gán vào biến let parkingChart
         type: "doughnut",
         data: {
           labels: ["Còn trống", "Đã đỗ", "Đặt trước"],
           datasets: [{
             label: "Trạng thái bãi đỗ",
             data: [available, occupied, reserved], // Dùng dữ liệu nhận được
             backgroundColor: ["rgba(40, 167, 69, 0.8)", "rgba(220, 53, 69, 0.8)", "rgba(255, 193, 7, 0.8)"],
             borderColor: ["#ffffff"], // Thêm border trắng cho đẹp
             borderWidth: 2,
             hoverOffset: 4
           }],
         },
         options: {
           responsive: true,
           maintainAspectRatio: false,
           plugins: {
             legend: { position: "bottom", labels: { padding: 15 } }, // Đưa legend xuống dưới
             title: { display: true, text: "Tỉ lệ chỗ đỗ xe", padding: { top: 10, bottom: 10 } },
             tooltip: {
                 callbacks: {
                    label: function(context) {
                         let label = context.label || '';
                         if (label) { label += ': '; }
                         if (context.parsed !== null) {
                             label += context.parsed;
                         }
                         return label;
                     }
                 }
             }
            },
            cutout: '60%' // Làm biểu đồ doughnut mỏng hơn chút
         },
       });
       console.log("Parking chart initialized.");
  }


  // Xử lý nút mở Barrier
  if (openBarrierBtn) {
    openBarrierBtn.addEventListener("click", async () => {
        const originalButtonHtml = openBarrierBtn.innerHTML;
        openBarrierBtn.disabled = true;
        openBarrierBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang mở...';
        try {
          // Fetch API có thể trả về text (nếu PHP không đặt content-type là json) hoặc json
          const result = await fetchApi("open_barrier.php", { method: "POST" });
          // Kiểm tra cả kiểu string và object (do fetchApi có thể trả về text)
          if ((typeof result === 'string' && result.includes("success")) || (result && result.status === "success")) {
             showAlert("Đã gửi lệnh mở Barrier thành công!", "success");
          } else {
             // Nếu là object và có message thì dùng message, nếu là string thì dùng string đó
             const errorMessage = (typeof result === 'object' && result?.message) ? result.message : (typeof result === 'string' ? result : "Có lỗi xảy ra khi gửi lệnh.");
             throw new Error(errorMessage);
          }
        } catch (error) {
          showAlert(`Lỗi khi mở barrier: ${error.message}`, "danger");
        } finally {
          openBarrierBtn.disabled = false;
          openBarrierBtn.innerHTML = originalButtonHtml;
        }
    });
  }

  // --- Quản lý Interval ---
  function stopDashboardUpdates() {
    if (dashboardUpdateInterval) {
        clearInterval(dashboardUpdateInterval);
        dashboardUpdateInterval = null;
        // console.log("Stopped Dashboard auto-update.");
    }
  }
  function startDashboardUpdates() {
     stopDashboardUpdates();
     updateDashboardData().then(() => { // Gọi lần đầu và đảm bảo chart được tạo nếu cần
         if (!parkingChart && parkingChartCanvas) {
             // Nếu updateDashboardData chạy xong mà chart chưa có, thử tạo lại (dự phòng)
              console.log("Attempting to initialize chart after first data fetch.");
             // Cần dữ liệu overview đã được fetch, nhưng để đơn giản, có thể gọi lại API hoặc chờ lần update sau
         }
     });
     dashboardUpdateInterval = setInterval(updateDashboardData, 5000);
     // console.log("Started Dashboard auto-update.");
  }

   // Bắt đầu cập nhật
   startDashboardUpdates();

  // Xử lý visibilitychange
  document.addEventListener("visibilitychange", function() {
     if(document.getElementById("dashboard-container")) { // Chỉ chạy nếu đang ở dashboard
        if (document.hidden) {
            stopDashboardUpdates();
        } else {
            startDashboardUpdates();
        }
     }
  });

} // --- Kết thúc initDashboard ---


// --- Logic cho trang Lịch sử (initHistoryPage) ---
function initHistoryPage() {
     const historyContainer = document.getElementById("history-container");
     if (!historyContainer) return;
     console.log("Initializing History Page...");

     const filterForm = document.getElementById("history-filters");
     const searchInput = document.getElementById("history-search");
     const slotFilter = document.getElementById("history-slot-filter");
     const startDateInput = document.getElementById("history-start-date");
     const endDateInput = document.getElementById("history-end-date");
     const historyTableBody = document.getElementById("historyTableBody");
     const loadingIndicator = document.getElementById("history-loading");
     const paginationContainer = document.getElementById("history-pagination");
     const clearHistoryBtn = document.getElementById("clearHistoryBtn");

     async function loadHistory(page = 1) {
         if (loadingIndicator) loadingIndicator.style.display = 'block';
         if (historyTableBody) historyTableBody.innerHTML = ''; // Clear table body

         try {
             const params = new URLSearchParams();
             if (searchInput?.value) params.append("search", searchInput.value);
             if (slotFilter?.value) params.append("slot_id", slotFilter.value);
             if (startDateInput?.value) params.append("start_date", startDateInput.value);
             if (endDateInput?.value) params.append("end_date", endDateInput.value);
             params.append("page", page);
             // params.append('limit', 15); // Set a limit

             const result = await fetchApi(`get_history.php?${params.toString()}`);

             if (result && result.status === 'success' && historyTableBody) {
                 if (!result.data || result.data.length === 0) {
                     historyTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted fst-italic">Không có dữ liệu phù hợp.</td></tr>';
                 } else {
                     result.data.forEach(log => {
                         const row = document.createElement('tr');
                         const entryTime = new Date(log.timestamp).toLocaleString("vi-VN", { dateStyle: 'medium', timeStyle: 'short' });
                         const actionText = log.action === 'entry' ? '<span class="badge text-bg-success">Vào</span>' : '<span class="badge text-bg-danger">Ra</span>';

                         row.innerHTML = `
                             <td>${log.log_id}</td>
                             <td>${log.uid || '<em class="text-muted">N/A</em>'}</td>
                             <td>${log.user_name || '<em class="text-muted">Không xác định</em>'}</td>
                             <td>${log.slot_name || '<em class="text-muted">N/A</em>'}</td>
                             <td>${entryTime}</td>
                             <td>${actionText}</td>
                         `;
                         historyTableBody.appendChild(row);
                     });
                 }
                 // TODO: Implement pagination based on result.pagination if API provides it
                 // updatePagination(result.pagination.currentPage, result.pagination.totalPages);
             } else {
                 throw new Error(result.message || "Không thể tải lịch sử.");
             }
         } catch (error) {
             console.error("Error loading history:", error);
             if (historyTableBody) historyTableBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Lỗi khi tải dữ liệu: ${error.message}</td></tr>`;
             // showAlert(`Lỗi khi tải lịch sử: ${error.message}`, "danger"); // Có thể không cần nếu lỗi đã hiện trong bảng
         } finally {
             if (loadingIndicator) loadingIndicator.style.display = 'none';
         }
     }

     // --- Placeholder Pagination ---
     function updatePagination(currentPage, totalPages) {
         if (!paginationContainer || !totalPages || totalPages <= 1) {
              if (paginationContainer) paginationContainer.innerHTML = ''; // Clear if no pages
              return;
         }
         let paginationHTML = '<nav aria-label="History navigation"><ul class="pagination justify-content-center">';
         // Previous button
         paginationHTML += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                             <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                                 <span aria-hidden="true">«</span>
                             </a>
                           </li>`;
         // Page numbers (basic example, needs improvement for many pages)
         for (let i = 1; i <= totalPages; i++) {
             paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                                  <a class="page-link" href="#" data-page="${i}">${i}</a>
                                </li>`;
         }
         // Next button
         paginationHTML += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                              <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                                  <span aria-hidden="true">»</span>
                              </a>
                            </li>`;
         paginationHTML += '</ul></nav>';
         paginationContainer.innerHTML = paginationHTML;

         // Add event listeners to new pagination links
         paginationContainer.querySelectorAll('.page-link').forEach(link => {
             link.addEventListener('click', (e) => {
                 e.preventDefault();
                 const page = parseInt(link.dataset.page);
                 if (page && page !== currentPage) {
                     loadHistory(page);
                 }
             });
         });
     }
      // --- End Placeholder Pagination ---


    // Gắn sự kiện cho form filter
    if (filterForm) {
      filterForm.addEventListener("submit", function(event) { // << THÊM HÀM XỬ LÝ VÀO ĐÂY
          event.preventDefault(); // Ngăn form gửi đi theo cách truyền thống
          console.log("Filter form submitted. Reloading history..."); // Thêm log để xác nhận
          loadHistory(1); // Load lại trang đầu tiên khi lọc
      });
    }
        // === XỬ LÝ NÚT XÓA LỊCH SỬ (PHIÊN BẢN ĐẦY ĐỦ) ===
        if (clearHistoryBtn) {
          // Lưu nội dung gốc của nút (bao gồm cả icon)
          const originalClearBtnHtml = clearHistoryBtn.innerHTML;
  
          clearHistoryBtn.addEventListener("click", async () => {
              // Hiển thị hộp thoại xác nhận chi tiết
              const confirmationMessage = "!!! CẢNH BÁO NGHIÊM TRỌNG !!!\n\n" +
                                         "Bạn sắp XÓA TOÀN BỘ dữ liệu lịch sử ra vào.\n" +
                                         "Hành động này KHÔNG THỂ HOÀN TÁC và toàn bộ thông tin về các lượt xe sẽ bị mất vĩnh viễn.\n\n" +
                                         "Để xác nhận bạn thực sự muốn tiếp tục, vui lòng nhập chính xác chữ 'DELETE' (viết hoa) vào ô bên dưới:";
              const confirmation = prompt(confirmationMessage);
  
              // Chỉ thực hiện nếu người dùng nhập đúng "DELETE"
              if (confirmation === "DELETE") {
                  clearHistoryBtn.disabled = true;
                  // Hiển thị spinner và text loading
                  clearHistoryBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xóa...';
  
                  try {
                      // Gọi API xóa lịch sử
                      const result = await fetchApi('clear_history.php', { method: 'POST' }); // Dùng POST
  
                      // Kiểm tra kết quả trả về từ API
                      if (result && result.status === 'success') {
                          showAlert('Đã xóa toàn bộ lịch sử ra vào thành công!', 'success');
                          // Load lại bảng lịch sử (lúc này sẽ trống hoặc báo không có dữ liệu)
                          loadHistory(1);
                      } else {
                          // Ném lỗi nếu API trả về status khác 'success' hoặc có lỗi khác
                           throw new Error(result?.message || 'Có lỗi không xác định xảy ra khi xóa lịch sử.');
                      }
                  } catch (error) {
                      // Hiển thị lỗi cho người dùng nếu gọi API thất bại
                      showAlert(`Lỗi khi xóa lịch sử: ${error.message}`, 'danger');
                      console.error("Error clearing history:", error);
                  } finally {
                      // Luôn kích hoạt lại nút và trả lại nội dung gốc dù thành công hay lỗi
                      clearHistoryBtn.disabled = false;
                      clearHistoryBtn.innerHTML = originalClearBtnHtml;
                  }
  
              } else if (confirmation !== null && confirmation !== "") { // Nếu người dùng có nhập gì đó nhưng sai
                   showAlert('Chuỗi xác nhận không khớp. Hành động xóa lịch sử đã bị hủy.', 'warning');
              } else if (confirmation === "") { // Nếu người dùng nhấn OK mà không nhập gì
                   showAlert('Bạn chưa nhập chuỗi xác nhận. Hành động xóa lịch sử đã bị hủy.', 'warning');
              }
              // Nếu confirmation === null (người dùng nhấn Cancel), không làm gì cả.
          });
      }
      // === KẾT THÚC XỬ LÝ NÚT XÓA ===

  // Load dữ liệu lần đầu
  loadHistory(1);

 } // --- Kết thúc initHistoryPage ---


// --- Logic cho trang Cài đặt (initSettingsPage) ---
function initSettingsPage() {
     const settingsContainer = document.getElementById("settings-container");
     if (!settingsContainer) return;
     console.log("Initializing Settings Page...");

     // --- Cài đặt Chung ---
     const generalSettingsForm = document.getElementById("general-settings-form");
     const settingSystemNameInput = document.getElementById("setting-system-name");
     const settingEspIpInput = document.getElementById("setting-esp32-ip");
     const settingNotificationEmailInput = document.getElementById("setting-notification-email");
     const settingEnableEmailsCheckbox = document.getElementById("setting-enable-emails");

     async function loadGeneralSettings() {
         const submitButton = generalSettingsForm?.querySelector('button[type="submit"]');
         if (submitButton) submitButton.disabled = true;
         try {
             // *** GỌI API get_settings.php ĐỂ LẤY DỮ LIỆU ***
             const result = await fetchApi('get_settings.php');
             if (result && result.status === 'success' && result.data) {
                 const settings = result.data;
                 if (settingSystemNameInput) settingSystemNameInput.value = settings.system_name || '';
                 if (settingEspIpInput) settingEspIpInput.value = settings.esp32_gate_ip || '';
                 if (settingNotificationEmailInput) settingNotificationEmailInput.value = settings.notification_email || '';
                 if (settingEnableEmailsCheckbox) settingEnableEmailsCheckbox.checked = settings.enable_entry_exit_emails || false; // Đảm bảo là boolean
                 console.log("Loaded general settings:", settings);
             } else {
                  console.warn("API get_settings.php did not return successful data.");
                  // Không ném lỗi ở đây, để form có thể dùng được dù không load được data cũ
                   showAlert(result?.message || "Không thể tải cài đặt hiện tại, sử dụng giá trị mặc định.", 'warning');
             }
         } catch (error) {
             showAlert(`Lỗi khi tải cài đặt: ${error.message}`, 'danger');
             console.error("Error loading general settings:", error);
         } finally {
             if (submitButton) submitButton.disabled = false;
         }
     }

     if (generalSettingsForm) {
         generalSettingsForm.addEventListener("submit", async (event) => {
             event.preventDefault();
             const submitButton = generalSettingsForm.querySelector('button[type="submit"]');
             const originalButtonHtml = submitButton.innerHTML;
             submitButton.disabled = true;
             submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang lưu...';
             const formData = new FormData(generalSettingsForm);

             try {
                 const result = await fetchApi("update_settings.php", { method: "POST", body: formData });
                 if (result && result.status === 'success') {
                     showAlert("Cập nhật cài đặt thành công!", "success");
                     if (settingSystemNameInput?.value) {
                         const mainTitle = document.querySelector('.navbar-text.text-white.fw-bold'); // Selector cụ thể hơn
                         if (mainTitle) mainTitle.textContent = settingSystemNameInput.value;
                         document.title = `${settingSystemNameInput.value} - Cài đặt`;
                     }
                 } else {
                     throw new Error(result.message || "Lỗi không xác định khi cập nhật.");
                 }
             } catch (error) {
                 showAlert(`Lỗi khi lưu cài đặt: ${error.message}`, "danger");
             } finally {
                 submitButton.disabled = false;
                 submitButton.innerHTML = originalButtonHtml;
             }
         });
     }

     // --- Quản lý người dùng ---
     const userTableBody = document.getElementById("user-table-body");
     const addUserModalElement = document.getElementById("addUserModal");
     const addUserForm = document.getElementById("add-user-form");
     const addUserAlertContainer = document.getElementById("add-user-alert-container");
     // Placeholder for Edit User Modal elements if/when implemented
     // const editUserModalElement = document.getElementById("editUserModal");
     // const editUserForm = document.getElementById("edit-user-form");
     // let editUserModalInstance = null;

     let addUserModalInstance = null;
     if (addUserModalElement) {
         addUserModalInstance = bootstrap.Modal.getOrCreateInstance(addUserModalElement);
         addUserModalElement.addEventListener('hidden.bs.modal', () => {
             addUserForm.reset();
             if(addUserAlertContainer) addUserAlertContainer.innerHTML = '';
         });
     }
      // if (editUserModalElement) { /* Initialize edit modal instance */ }


     async function loadUsers() {
         // ... (Giữ nguyên code loadUsers đã hoàn thiện) ...
          if (!userTableBody) return;
          userTableBody.innerHTML = '<tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm"></span> Đang tải...</td></tr>';
          try {
              const result = await fetchApi('get_users.php');
              if (result?.status === 'success') { /* Check an toàn hơn */
                  userTableBody.innerHTML = '';
                  if (!result.data || result.data.length === 0) {
                      userTableBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted fst-italic">Chưa có người dùng nào.</td></tr>';
                  } else {
                      result.data.forEach(user => {
                          const row = userTableBody.insertRow(); // Cách khác để tạo row
                          row.dataset.userId = user.id; // Giả sử API trả về id
                          row.innerHTML = `
                              <td class="user-uid">${user.uid}</td>
                              <td class="user-name">${user.name || '<em class="text-muted">N/A</em>'}</td>
                              <td class="user-email">${user.email || '-'}</td>
                              <td>
                                  <button class="btn btn-sm btn-outline-primary btn-edit-user" data-uid="${user.uid}" title="Sửa người dùng">
                                     <i class="fas fa-edit"></i>
                                  </button>
                                  <button class="btn btn-sm btn-outline-danger btn-delete-user" data-uid="${user.uid}" title="Xóa người dùng">
                                     <i class="fas fa-trash-alt"></i>
                                  </button>
                              </td>
                          `;
                      });
                  }
                  attachUserActionListeners();
              } else {
                  throw new Error(result?.message || 'Lỗi tải danh sách người dùng');
              }
          } catch (error) {
              console.error("Error loading users:", error);
              userTableBody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Lỗi tải danh sách: ${error.message}</td></tr>`;
          }
     }

     if (addUserForm && addUserModalInstance) {
         // ... (Giữ nguyên code xử lý submit add user) ...
          addUserForm.addEventListener("submit", async (event) => {
              event.preventDefault();
              const submitButton = addUserForm.querySelector('button[type="submit"]');
              const originalButtonHtml = submitButton.innerHTML;
              submitButton.disabled = true;
              submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang thêm...';
              if (addUserAlertContainer) addUserAlertContainer.innerHTML = '';
              const formData = new FormData(addUserForm);
              try {
                  const result = await fetchApi('add_user.php', { method: 'POST', body: formData });
                  if (result?.status === 'success') {
                      showAlert('Thêm người dùng thành công!', 'success');
                      addUserForm.reset();
                      addUserModalInstance.hide();
                      loadUsers();
                  } else {
                      throw new Error(result?.message || 'Lỗi khi thêm người dùng.');
                  }
              } catch (error) {
                 if(addUserAlertContainer) {
                      const wrapper = document.createElement("div");
                      wrapper.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">${error.message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                      addUserAlertContainer.append(wrapper);
                 } else { showAlert(`Lỗi khi thêm: ${error.message}`, 'danger'); }
              } finally {
                  submitButton.disabled = false;
                  submitButton.innerHTML = originalButtonHtml;
              }
          });
     }

     function attachUserActionListeners() {
         // ... (Giữ nguyên code xử lý nút Edit và Delete) ...
          // Sửa
          userTableBody.querySelectorAll(".btn-edit-user").forEach(button => {
              button.removeEventListener('click', handleEditUserClick); // Gỡ listener cũ phòng trùng lặp
              button.addEventListener("click", handleEditUserClick);
          });
           // Xóa
           userTableBody.querySelectorAll(".btn-delete-user").forEach(button => {
                button.removeEventListener('click', handleDeleteUserClick); // Gỡ listener cũ
                button.addEventListener("click", handleDeleteUserClick);
           });
     }

      // Hàm xử lý khi nhấn nút Sửa (tách ra)
      function handleEditUserClick(event) {
          const buttonElement = event.currentTarget;
          const uid = buttonElement.dataset.uid;
          // TODO: Implement Edit User
          console.log(`Edit user clicked: ${uid}`);
          showAlert("Chức năng sửa người dùng chưa được cài đặt.", "info");
          // Ví dụ:
          // const row = buttonElement.closest('tr');
          // document.getElementById('edit-user-uid-hidden').value = uid;
          // document.getElementById('edit-user-name-input').value = row.querySelector('.user-name').textContent;
          // ... fill other fields ...
          // editUserModalInstance.show();
      }

       // Hàm xử lý khi nhấn nút Xóa (tách ra)
       async function handleDeleteUserClick(event) {
            const buttonElement = event.currentTarget;
            const uid = buttonElement.dataset.uid;
            const userName = buttonElement.closest('tr')?.querySelector('.user-name')?.textContent || `UID: ${uid}`;
            if (confirm(`Bạn có chắc chắn muốn xóa người dùng "${userName}"?\nHành động này không thể hoàn tác.`)) {
                const originalButtonIcon = buttonElement.innerHTML;
                buttonElement.disabled = true;
                buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                try {
                    const result = await fetchApi('delete_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ uid: uid }) });
                    if (result?.status === 'success') {
                        showAlert('Xóa người dùng thành công!', 'success');
                        loadUsers();
                    } else {
                        throw new Error(result?.message || 'Lỗi khi xóa người dùng.');
                    }
                } catch (error) {
                    showAlert(`Lỗi khi xóa: ${error.message}`, 'danger');
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalButtonIcon;
                }
            }
       }


     // Load dữ liệu ban đầu
     loadGeneralSettings();
     loadUsers();

 } // --- Kết thúc initSettingsPage ---


// --- Khởi tạo khi DOM đã sẵn sàng ---
document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM fully loaded and parsed");

  initDashboard();
  initHistoryPage();
  initSettingsPage();

  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});