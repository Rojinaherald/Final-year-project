function exploreProject() {
  alert("Welcome! More features coming soon.");
}

document.getElementById('login-form').addEventListener('submit', function (event) {
  let valid = true;
  let loginId = document.getElementById('login_id');
  let password = document.getElementById('password');

  // Reset previous error messages
  document.getElementById('login-id-error').textContent = '';
  document.getElementById('password-error').textContent = '';

  // Basic validation
  if (!loginId.value) {
      valid = false;
      document.getElementById('login-id-error').textContent = 'Login ID is required';
  }

  if (!password.value) {
      valid = false;
      document.getElementById('password-error').textContent = 'Password is required';
  }

  if (!valid) {
      event.preventDefault();
  }
});

document.getElementById("uploadFile").addEventListener("click", function() {
    let fileInput = document.getElementById("fileInput");
    let fileList = document.getElementById("fileList");
    let progressBar = document.getElementById("progress");

    if (fileInput.files.length > 0) {
        let file = fileInput.files[0];

        // Simulated upload progress
        progressBar.style.width = "0%";
        let progress = 0;
        let interval = setInterval(() => {
            progress += 10;
            progressBar.style.width = progress + "%";
            if (progress >= 100) {
                clearInterval(interval);

                // Change the text from "No files uploaded yet" to uploaded file details
                if (fileList.querySelector('p')) {
                    fileList.querySelector('p').remove(); // Remove the initial message
                }

                let fileItem = document.createElement("div");
                fileItem.classList.add("file-item");
                fileItem.innerHTML = `<span>📄 ${file.name}</span>`;
                fileList.appendChild(fileItem);

                // Reset input for another upload
                fileInput.value = ""; 
            }
        }, 300);
    }
});
