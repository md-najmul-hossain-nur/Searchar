  function previewImage(event, previewId) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = () => document.getElementById(previewId).src = reader.result;
        reader.readAsDataURL(file);
      }
    }
   
