console.log("Server file is running...");
const express = require("express");
const axios = require("axios");
require("dotenv").config();

const app = express();
app.use(express.json());

const PAGE_ID = process.env.PAGE_ID;
const ACCESS_TOKEN = process.env.PAGE_ACCESS_TOKEN;

// API to post on Facebook Page
app.post("/post", async (req, res) => {
  const { message } = req.body;

  try {
    const response = await axios.post(
      `https://graph.facebook.com/v18.0/${PAGE_ID}/feed`,
      {
        message: message,
        access_token: ACCESS_TOKEN,
      }
    );

    res.json({
      success: true,
      postId: response.data.id,
    });
  } catch (error) {
    console.error(error.response?.data || error.message);

    res.status(500).json({
      success: false,
      error: error.response?.data || error.message,
    });
  }
});

app.listen(3000, () => {
  console.log("Server running on http://localhost:3000");
});
console.log("PAGE_ID:", PAGE_ID);
console.log("TOKEN:", ACCESS_TOKEN ? "Loaded" : "Missing");