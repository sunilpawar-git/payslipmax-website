#!/bin/bash

# PayslipMax Workspace Setup Script
echo "🚀 Setting up PayslipMax development workspace..."

# Check if app directory exists
if [ ! -d "../PayslipMax-App" ]; then
    echo "⚠️  PayslipMax-App directory not found at ../PayslipMax-App"
    echo "Please update the path in PayslipMax-Complete.code-workspace"
fi

# Create .cursor directory if it doesn't exist
mkdir -p .cursor

# Set permissions for uploads directory
chmod 755 api/uploads 2>/dev/null || echo "ℹ️  Uploads directory permissions will be set on server"

# Check if workspace file exists
if [ -f "PayslipMax-Complete.code-workspace" ]; then
    echo "✅ Workspace configuration created"
    echo "📁 Open this file in Cursor to load both projects"
else
    echo "❌ Workspace file not found"
fi

echo ""
echo "🎯 Next Steps:"
echo "1. Open 'PayslipMax-Complete.code-workspace' in Cursor"
echo "2. Both website and app will be available in the sidebar"
echo "3. Use @ symbol to reference files across projects"
echo "4. Cursor AI will understand both codebases simultaneously"
echo ""
echo "🔧 For app debugging:"
echo "- Use Flutter DevTools for app debugging"
echo "- Check deep link handling in main.dart"
echo "- Verify API endpoints are accessible from app"
echo ""
echo "✨ Happy coding!" 