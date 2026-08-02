# Global News Intelligence Platform (GNIP)

## Overview
This document defines the software specification for a news aggregation platform.

### Objectives
- Collect news from global RSS, APIs and supported websites.
- Orchestrate workflows with n8n.
- Store articles and logs in PostgreSQL.
- Publish articles to GitHub.
- Translate articles into Thai while preserving the original language.
- Build a Node.js + Next.js website.
- Send daily news digests to LINE OA.

## Core Components
- n8n: workflow orchestration
- PostgreSQL: article, source and workflow logs
- Node.js Backend API
- Next.js Frontend
- GitHub Repository Storage
- AI Translation and Summarization
- LINE OA Integration

## Initial News Categories
- World
- Politics
- Business
- Finance
- Technology
- AI
- Cybersecurity
- Science
- Health
- Environment
- Space
- Sports
- Entertainment
- Thailand

## Next revisions
This document will be expanded into a complete enterprise specification covering architecture, database schema, APIs, workflows, security, deployment, and operations.