document.addEventListener('DOMContentLoaded', function () {
  const totalUsersEl = document.getElementById('totalUsers');
  const dailyVisitorsEl = document.getElementById('dailyVisitors');
  const availableBooksEl = document.getElementById('activeBooks'); 
  const borrowedBooksEl = document.getElementById('borrowedBooks');
  
  const totalStudentsEl = document.getElementById('totalStudents');
  const totalFacultyEl = document.getElementById('totalFaculty');
  const totalStaffEl = document.getElementById('totalStaff');

  const topVisitorsTableBody = document.getElementById('topVisitorsTableBody');
  const popularBooksTableBody = document.getElementById('popularBooksTableBody');
  const recentActivitiesTableBody = document.getElementById('recentActivitiesTableBody');
  const overdueBooksTableBody = document.getElementById('overdueBooksTableBody');
  const weeklyActivityCtx = document.getElementById('weeklyActivityChart')?.getContext('2d');

  let weeklyActivityChartInstance = null;

  async function loadDashboardData() {
    try {
      const response = await fetch(`api/dashboard/getData`);
      const result = await response.json();

      if (result.success) {
        const data = result.data;

        if (totalUsersEl) totalUsersEl.textContent = data.totalUsers;
        if (dailyVisitorsEl) dailyVisitorsEl.textContent = data.attendance_today;
        if (availableBooksEl) availableBooksEl.textContent = data.availableBooks;
        if (borrowedBooksEl) borrowedBooksEl.textContent = data.borrowed_books;

        if (totalStudentsEl) totalStudentsEl.textContent = data.students;
        if (totalFacultyEl) totalFacultyEl.textContent = data.faculty;
        if (totalStaffEl) totalStaffEl.textContent = data.staff;

        const totalUsersSub = document.querySelector("#totalUsers + p");
        if (totalUsersSub) totalUsersSub.textContent = `+${data.usersAddedThisMonth} this month`;

        const availableSub = document.querySelector("#activeBooks + p");
        if (availableSub) availableSub.textContent = `${data.availableBooksPercent}% available`;

        const borrowedSub = document.querySelector("#borrowedBooks + p");
        if (borrowedSub) borrowedSub.textContent = `${data.borrowedBooksPercent}% of total books`;

        renderTopVisitorsTable(result.topVisitors);
        renderWeeklyChart(result.weeklyActivity);
        renderPopularBooksTable(result.popularBooks);
        renderRecentActivities(result.recentActivities);
        renderOverdueBooksTable(result.overdueBooks);
      }
    } catch (error) {
      console.error('Error loading dashboard:', error);
    }
  }

  function renderOverdueBooksTable(books) {
    if (!overdueBooksTableBody) return;
    if (!books || books.length === 0) {
      overdueBooksTableBody.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-gray-400 italic text-xs uppercase font-bold tracking-widest">No records found</td></tr>';
      return;
    }
    overdueBooksTableBody.innerHTML = books.map((b, index) => {
      const truncatedTitle = b.title.length > 40 ? b.title.substring(0, 40) + '...' : b.title;
      return `
        <tr class="hover:bg-green-50/30 transition-colors">
          <td class="px-4 py-3 text-left font-black text-green-600 text-[13px]">${index + 1}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-tight text-[13px]">${b.borrower_name || "Unknown"}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[13px]">${truncatedTitle}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[13px]">${b.accession_number}</td>
          <td class="px-4 py-3 text-right font-black text-gray-800 text-[13px]">${b.days_overdue} Days</td>
        </tr>
      `;
    }).join('');
  }

  function renderPopularBooksTable(books) {
    if (!popularBooksTableBody) return;
    if (!books || books.length === 0) {
      popularBooksTableBody.innerHTML = '<tr><td colspan="4" class="py-10 text-center text-gray-400 italic text-xs uppercase font-bold tracking-widest">No records found</td></tr>';
      return;
    }
    popularBooksTableBody.innerHTML = books.map((b, index) => {
      const truncatedTitle = b.title.length > 45 ? b.title.substring(0, 45) + '...' : b.title;
      return `
        <tr class="hover:bg-orange-50/30 transition-colors">
          <td class="px-4 py-3 text-left font-black text-orange-600 text-[13px]">${index + 1}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-tight text-[13px]" title="${b.title}">${truncatedTitle}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[13px]">${b.accession_number}</td>
          <td class="px-4 py-3 text-right font-black text-gray-800 text-[13px]">${b.borrow_count}</td>
        </tr>
      `;
    }).join('');
  }

  function renderRecentActivities(activities) {
    if (!recentActivitiesTableBody) return;
    if (!activities || activities.length === 0) {
      recentActivitiesTableBody.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-gray-400 italic text-xs uppercase font-bold tracking-widest">No recent activities</td></tr>';
      return;
    }
    recentActivitiesTableBody.innerHTML = activities.map((a, index) => {
      const d = new Date(a.created_at);
      const datePart = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      const timePart = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
      
      const rawUser = a.full_name || a.username || "System";
      const truncatedUser = rawUser.length > 15 ? rawUser.substring(0, 15) + '...' : rawUser;
      const truncatedDetails = a.details.length > 30 ? a.details.substring(0, 30) + '...' : a.details;
      
      return `
        <tr class="hover:bg-orange-50/30 transition-colors">
          <td class="px-4 py-3 text-left font-black text-orange-600 text-[13px]">${index + 1}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-tight text-[12px]" title="${rawUser}">${truncatedUser}</td>
          <td class="px-4 py-3 text-left">
            <span class="font-black text-orange-700 uppercase tracking-tighter text-[10px] bg-orange-100 px-2 py-0.5 rounded-md">
              ${a.action}
            </span>
          </td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[11px]" title="${a.details}">
            ${truncatedDetails}
          </td>
          <td class="px-4 py-3 text-right">
            <div class="flex flex-col leading-tight tabular-nums">
              <span class="font-black text-gray-800 text-[11px] whitespace-nowrap">${datePart}</span>
              <span class="font-bold text-gray-400 text-[9px] uppercase whitespace-nowrap">${timePart}</span>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function renderTopVisitorsTable(visitors) {
    if (!topVisitorsTableBody) return;
    if (!visitors || visitors.length === 0) {
      topVisitorsTableBody.innerHTML = '<tr><td colspan="5" class="py-10 text-center text-gray-400 italic text-xs uppercase font-bold tracking-widest">No records found</td></tr>';
      return;
    }
    topVisitorsTableBody.innerHTML = visitors.map((v, index) => {
      // Split name and get only First and Last (assuming format is "First Middle Last")
      const nameParts = (v.user_name || "Unknown User").trim().split(/\s+/);
      let displayName = v.user_name;
      
      if (nameParts.length > 2) {
        // If there are more than 2 parts, we take the first and the last one
        displayName = `${nameParts[0]} ${nameParts[nameParts.length - 1]}`;
      } else if (nameParts.length === 2) {
        displayName = `${nameParts[0]} ${nameParts[1]}`;
      }

      return `
        <tr class="hover:bg-orange-50/30 transition-colors">
          <td class="px-4 py-3 text-left font-black text-orange-600 text-[13px]">${index + 1}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-700 uppercase tracking-tight text-[13px]">${displayName}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[13px]">${v.student_number}</td>
          <td class="px-4 py-3 text-left font-bold text-gray-600 uppercase tracking-tight text-[13px]">${v.year_level} - ${v.section}</td>
          <td class="px-4 py-3 text-right font-black text-gray-800 text-[13px]">${v.visits}</td>
        </tr>
      `;
    }).join('');
  }

  function renderWeeklyChart(activity) {
    if (!weeklyActivityCtx) return;
    const labels = activity.map(w => w.day);
    const visitorsData = activity.map(w => w.visitors);
    const borrowsData = activity.map(w => w.borrows);
    if (weeklyActivityChartInstance) weeklyActivityChartInstance.destroy();
    weeklyActivityChartInstance = new Chart(weeklyActivityCtx, {
      type: "line",
      data: {
        labels: labels,
        datasets: [{
            label: "Visitors",
            data: visitorsData,
            borderColor: "#10b981",
            backgroundColor: "rgba(16,185,129,0.1)",
            tension: 0.4,
            fill: true
          },
          {
            label: "Checkouts",
            data: borrowsData,
            borderColor: "#f59e0b",
            backgroundColor: "rgba(245,158,11,0.1)",
            tension: 0.4,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  loadDashboardData();
});