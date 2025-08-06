provider "github" {
  token = jsondecode(data.aws_secretsmanager_secret_version.github_token.secret_string)["token"]
  owner = var.github_owner
}

# Register webhook with GitHub (for dev environment only)
resource "github_repository_webhook" "aws_codepipeline" {
  count = var.github_branch == "dev" ? 1 : 0
  
  repository = var.github_repo
  
  configuration {
    url          = aws_codepipeline_webhook.github_webhook[0].url
    content_type = "json"
    insecure_ssl = false
    secret       = jsondecode(data.aws_secretsmanager_secret_version.github_token.secret_string)["token"]
  }

  events = ["push"]
}
