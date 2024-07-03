
// This code is an adaptation of that acquired from this URL @ 2024-06-29
// https://www.geeksforgeeks.org/how-to-design-a-simple-calendar-using-javascript/

const calendar = document.querySelector (".calendar-container");
if (calendar) {

    let date = new Date ();
    let year = date.getFullYear ();
    let month = date.getMonth ();

    const open = document.querySelector (".calendar-open");
    const close = document.querySelector (".calendar-close");
    const day = document.querySelector (".calendar-dates");
    const currdate = document.querySelector (".calendar-current-date");
    const prenexIcons = document.querySelectorAll (".calendar-navigation span");

    let calendarDraws = {};
    // calendarData is assumed to be an array of yyyy-mm-dd values
    let i = 0;
    for (i in calendarData) {
        calendarDraws[calendarData[i]] = true;
    }

    // Array of month names
    const months = [
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December"
    ];

    // Function to generate the calendar
    const manipulate = () => {

        // Get the first day of the month
        let dayone = new Date(year, month, 1).getDay();

        // Get the last date of the month
        let lastdate = new Date(year, month + 1, 0).getDate();

        // Get the day of the last date of the month
        let dayend = new Date(year, month, lastdate).getDay();

        // Get the last date of the previous month
        let monthlastdate = new Date(year, month, 0).getDate();

        // Variable to store the generated calendar HTML
        let lit = "";

        // Loop to add the last dates of the previous month
        for (let i = dayone; i > 0; i--) {
            lit +=
                `<li class="inactive">${monthlastdate - i + 1}</li>`;
        }

        // Loop to add the dates of the current month
        let count = 0;
        for (let i = 1; i <= lastdate; i++) {

            // Check if the current date is today
            let isToday = i === date.getDate()
                && month === new Date().getMonth()
                && year === new Date().getFullYear()
                ? "active"
                : "";


            // Is this a draw day?
            let d = year + '-' + (month+1).toString().padStart(2,'0') + '-' + i.toString().padStart(2,'0');
            let isDraw = calendarDraws[d]
                ? 'draw'
                : '';
            // Show order of dates
            let sup = '';
            if (isDraw=='draw') {
                count++;
                sup = `<label>${count}</label>`;
            }

            lit += `<li class="${isToday} ${isDraw}" data-value="${i}">${i}${sup}</li>`;
        }

        // Loop to add the first dates of the next month
        for (let i=dayend;i<6;i++) {
            lit += `<li class="inactive">${i - dayend + 1}</li>`
        }

        // Update the text of the current date element 
        // with the formatted current month and year
        currdate.innerText = `${months[month]} ${year}`;

        // update the HTML of the dates element 
        // with the generated calendar
        day.innerHTML = lit;

        // Add event listeners to dates
        var d,ds;
        ds = day.querySelectorAll ('data-value');
        for (d of ds) {
            d.addEventListener ('click',selectDay);
        }
    }

    manipulate ();

    // Attach a click event listener to each icon
    prenexIcons.forEach(icon => {

        // When an icon is clicked
        icon.addEventListener("click", () => {

            // Check if the icon is "calendar-prev"
            // or "calendar-next"
            month = icon.id === "calendar-prev" ? month - 1 : month + 1;

            // Check if the month is out of range
            if (month < 0 || month > 11) {

                // Set the date to the first day of the 
                // month with the new year
                date = new Date (year,month,new Date().getDate());

                // Set the year to the new year
                year = date.getFullYear ();

                // Set the month to the new month
                month = date.getMonth ();
            }

            else {

                // Set the date to the current date
                date = new Date ();
            }

            // Call the manipulate function to 
            // update the calendar display
            manipulate ();
        });
    });

    open.addEventListener ('click',function(evt){evt.preventDefault();calendar.style.display='block'});
    close.addEventListener ('click',function(evt){calendar.style.display='none'});

    // Handle clicking on a day
    const selectDay = (evt) => {
        var i;
        if (open.dataset.selectandclose) {
            i = document.getElementById (open.dataset.selectandclose);
            if (i) {
                i.value = evt.currentTarget.dataset.value;
                calendar.style.display='none';
            }
        }
    }

}

